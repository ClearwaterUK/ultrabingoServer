<?php

function handleError(\WebSocket\Connection $connection,\WebSocket\Exception\Exception $exception):void
{
    global $gameCoordinator;

    logError("Unclean disconnect from client - lost connection or alt-f4'd?" );
    logError($exception->getMessage() . " (".$exception->getCode().")");

    $connectionHash = md5(strval($connection));

    //Remove the dropped connection from the game that it was in.
    $gameDetails = getPlayerFromConnectionTable($connectionHash);
    if($gameDetails != null)
    {
        //Go into the room id
        $associatedGame = $gameCoordinator->currentGames[$gameDetails[0]];
        $username = $gameDetails[1];
        $steamId = $gameDetails[2];

        LogWarn("Player who timed out:".$username);
        //If the SteamID of the player who dropped is the host of the associated game, end the game for all players and remove
        //the game from the current game list.
        if($associatedGame->gameHost == $steamId)
        {
            logWarn("Client who dropped was the host of game ".$gameDetails[0]. ", ending game for all connected players");
            $gameCoordinator->disconnectAllPlayers($gameDetails[0],$connection,"HOSTDROPPED");
            $gameCoordinator->destroyGame($gameDetails[0]);
            removeGame($gameDetails[0]);
        }
        else
        {
            $indexToUnset = "";
            foreach($associatedGame->currentPlayers as $playerSteamId => $playerObj)
            {
                if($playerObj->username === $username)
                {
                    $indexToUnset = $playerSteamId;
                }
                else
                {
                    $timeoutNotif = new TimeoutNotification($username,$steamId);
                    $em = new EncapsulatedMessage("TimeoutNotification",json_encode($timeoutNotif));
                    sendEncodedMessage($em,$playerObj->websocketConnection);
                }
            }
            unset($associatedGame->currentPlayers[$indexToUnset]);
        }
        unregisterConnection($steamId);
    }
    $connection->disconnect();
}

function enumerateServerConnections($server):void
{
    logWarn($server->getConnectionCount() . " active connections");
}

function onClientConnect($server):void
{
    logMessage("Incoming connection");
    enumerateServerConnections($server);
}

function onClientDisconnect($server,$connection):void
{
    logMessage("Client has disconnected");
    enumerateServerConnections($server);
    if($connection->isConnected()) {$connection->disconnect();}

}

function onMessageRecieved($message,$connection):void
{
    global $gameCoordinator;

    $executionTime = 15;
    set_time_limit($executionTime);

    $receivedJson = decodeMessage($message);
    if(!isset($receivedJson["messageType"]))
    {
        logError("Message type was not defined, dropping message");
        return;
    }

    try {
        $messageType = $receivedJson["messageType"];
        switch($messageType)
        {
            case "CreateRoom":
            {
                //Make sure the steamID or the IP isn't banned
                if(checkBan($receivedJson["hostSteamId"],explode(":",$connection->getRemoteName())[0]))
                {
                    logError("This SteamID or IP address is banned from the mod!");
                    $status = "ban";
                    $crr = new CreateRoomResponse($status,-1);
                    $em = new EncapsulatedMessage("CreateRoomResponse",json_encode($crr));
                    sendEncodedMessage($em,$connection);
                    $connection->close();
                    return;
                }

                logWarn("Creating new game in DB");
                $roomId = createRoomInDatabase($receivedJson);
                if($roomId <> null && $roomId <> 0)
                {
                    //Create the room
                    $status = "ok";
                    $game = $gameCoordinator->createGame($roomId,$receivedJson["hostSteamName"],$connection,$receivedJson["hostSteamId"]);
                    logMessage("Game created and set up with id ".$roomId);
                    $crr = new CreateRoomResponse($status,$roomId,$game);
                    $em = new EncapsulatedMessage("CreateRoomResponse",json_encode($crr));
                }
                else{
                    logError("Failed to create room!");
                    $status = "err";
                    $crr = new CreateRoomResponse($status,$roomId);
                    $em = new EncapsulatedMessage("CreateRoomResponse",json_encode($crr));
                }

                //Send back the response to the client
                sendEncodedMessage($em,$connection);
                break;
            }

            case "JoinRoom":
            {
                logMessage($receivedJson['username']." wants to join game ".$receivedJson['roomId']);
                //Make sure the steamID or the IP isn't banned
                if(checkBan($receivedJson["steamId"],explode(":",$connection->getRemoteName())[0]))
                {
                    logError("This SteamID or IP address is banned from the mod!");
                    $status = -5;
                    $crr = new JoinRoomResponse($status,-1,null);
                    $em = new EncapsulatedMessage("JoinRoomResponse",json_encode($crr));
                    sendEncodedMessage($em,$connection);
                    $connection->close();
                    return;
                }

                // Make sure the steamID wasn't already kicked from the game
                if(checkKick($receivedJson['roomId'],$receivedJson["steamId"])) {
                    logError("This SteamID was kicked from this game!");
                    $status = -6;
                    $crr = new JoinRoomResponse($status, -1, null);
                    $em = new EncapsulatedMessage("JoinRoomResponse", json_encode($crr));
                    sendEncodedMessage($em, $connection);
                    $connection->close();
                }

                $gameToJoin = $gameCoordinator->joinGame($receivedJson['roomId'],$receivedJson['username'],$receivedJson['steamId'],$connection);

                $status = (gettype($gameToJoin) == "integer") ? $gameToJoin : 0;
                $roomId = $receivedJson['roomId'];
                $room = ($status == 0) ? $gameCoordinator->currentGames[$receivedJson['roomId']] : null;

                $jrr = new JoinRoomResponse($status,$roomId,$room);
                $em = new EncapsulatedMessage("JoinRoomResponse",json_encode($jrr));
                sendEncodedMessage($em,$connection);

                break;
            }

            case "UpdateRoomSettings":
            {
                logWarn("Updating room settings");
                if(verifyConnection($receivedJson['ticket'],true))
                {
                    logMessage("Updating settings for room ".$receivedJson['roomId']);
                    $gameCoordinator->updateGameSettings($receivedJson);
                }
                break;
            }

            case "StartGame":
            {
                if(verifyConnection($receivedJson['ticket'],true))
                {
                    logWarn("Starting game ".$receivedJson['roomId']);
                    $gameCoordinator->startGame($receivedJson['roomId']);
                }
                break;
            }

            case "KickPlayer":
            {
                if(verifyConnection($receivedJson['ticket'],true))
                {
                    logWarn("Kicking player ". $receivedJson['playerToKick']." from game ".$receivedJson['gameId']);
                    $gameCoordinator->kickPlayer($receivedJson['gameId'],$receivedJson['playerToKick']);
                }
            }

            case "LeaveGame":
            {
                logMessage("Player wants to leave game ".$receivedJson['roomId']);
                if($receivedJson['username'] == null)
                {
                    logError("Given username was null, aborting!");
                    break;
                }

                $checkResult = $gameCoordinator->checkPlayerBeforeRemoving($receivedJson['username'],$receivedJson['roomId'],$receivedJson['steamId']);
                if($checkResult < 0)
                {
                    logError("Unable to remove the specified player");
                    break;
                }
                else
                {
                    //If the player is the host...
                    if($checkResult == 1)
                    {
                        //...disconnect all players before deleting the game
                        $gameCoordinator->disconnectAllPlayers($receivedJson['roomId'],$connection,"HOSTLEFTGAME");

                        //Then delete the game.
                        $gameCoordinator->destroyGame($receivedJson['roomId']);
                    }
                    //If player is not the host, simply disconnect and remove just the player.
                    else
                    {
                        $gameCoordinator->disconnectPlayer($receivedJson['roomId'],$receivedJson['username'],$receivedJson['steamId'],$connection);
                    }
                }
                break;
            }

            case "SubmitRun":
            {
                $gameId = $receivedJson['gameId'];

                logMessage("Player is submitting run in game ".$receivedJson['gameId']);

                if(!verifyConnection($receivedJson['ticket']))
                {
                    logWarn("Invalid Steam ticket or player is not in game, rejecting submission");
                    return;
                }

                if($gameCoordinator->verifyRunSubmission($receivedJson))
                {
                    $submitResult = $gameCoordinator->submitRun($receivedJson);
                    if($submitResult >= 0)
                    {
                        //Check if the claimed level resulted in a bingo.
                        $hasObtainedBingo = $gameCoordinator->currentGames[$gameId]->checkForBingo($receivedJson['team'],$receivedJson['row'],$receivedJson['column']);

                        $levelDisplayName = $gameCoordinator->currentGames[$gameId]->grid->levelTable[$receivedJson['row']."-".$receivedJson['column']]->levelName;

                        $claimBroadcast = new ClaimedLevelBroadcast($receivedJson['playerName'],$receivedJson['team'],$levelDisplayName,$submitResult,$receivedJson['row'],$receivedJson['column'],$receivedJson['time'],$receivedJson['style']);

                        foreach($gameCoordinator->currentGames[$gameId]->currentPlayers as $playerSteamId => &$playerObj)
                        {
                            $message = new EncapsulatedMessage("LevelClaimed",json_encode($claimBroadcast));
                            sendEncodedMessage($message,$playerObj->websocketConnection);
                        }
                        if($hasObtainedBingo)
                        {
                            $gameToEnd = $gameCoordinator->currentGames[$gameId];

                            //Get all the necessary endgame stats to send to each player.
                            $winningPlayers = array_values($gameToEnd->teams[$receivedJson['team']]);

                            $endTime = new DateTime();
                            logWarn("Ending game ".$gameId." at ".$endTime->format("Y-m-d h:i:s A"));

                            $elapsedTime = $gameToEnd->startTime->diff($endTime)->format(("%H:%I:%S"));
                            logMessage("Elapsed time of game: ".$elapsedTime);

                            $claims = $gameToEnd->numOfClaims;

                            $bingoSignal = new EndGameSignal($receivedJson['team'],$winningPlayers,$elapsedTime,$claims,$gameToEnd->firstMapClaimed,$gameToEnd->lastMapClaimed,$gameToEnd->bestStatValue,$gameToEnd->bestStatMap);
                            foreach($gameCoordinator->currentGames[$gameId]->currentPlayers as $playerSteamId => &$playerObj)
                            {
                                $message = new EncapsulatedMessage("GameEnd",json_encode($bingoSignal));
                                sendEncodedMessage($message,$playerObj->websocketConnection);
                            }
                        }
                    }
                }
                else
                {
                    logWarn("Run submission was invalid - rejecting");
                }
                break;
            }
            case "UpdateMapPool":
            {
                if(verifyConnection($receivedJson['ticket'],true))
                {
                    $gameId = $receivedJson['gameId'];
                    if(array_key_exists($gameId,$gameCoordinator->currentGames))
                    {
                        logMessage("Updating map pools for game ".$gameId);
                        $gameCoordinator->currentGames[$gameId]->updateMapPool(array_values($receivedJson["mapPoolIds"]));
                    }
                    else
                    {
                        logError("Tried to update map pools for game ".$gameId."but it doesn't exist!");
                    }
                }
                break;
            }
            case "UpdateTeamSettings":
            {
                if(verifyConnection($receivedJson['ticket'],true))
                {
                    $gameId = $receivedJson['gameId'];
                    if(array_key_exists($gameId,$gameCoordinator->currentGames))
                    {
                        logMessage("Updating teams for game ".$gameId);
                        $gameCoordinator->currentGames[$gameId]->updateTeams($receivedJson["teams"]);
                    }
                    else
                    {
                        logError("Tried to update teams for game ".$gameId."but it doesn't exist!");
                    }
                }
                break;
            }
            case "ClearTeams":
            {
                if(verifyConnection($receivedJson['ticket'],true))
                {
                    $gameId = $receivedJson['gameId'];
                    if(array_key_exists($gameId,$gameCoordinator->currentGames))
                    {
                        logMessage("Clearing teams of game ".$gameId);
                        $gameCoordinator->currentGames[$gameId]->clearTeams();
                    }
                    else
                    {
                        logError("Tried to clear teams for game ".$gameId."but it doesn't exist!");
                    }
                }

                break;
            }
            case "CheatActivation":
            {
                $gameCoordinator->humiliatePlayer($receivedJson['gameId'],$receivedJson['steamId']);
                break;
            }
            case "RegisterTicket":
            {
                logWarn("Registering new connection");
                registerConnection($connection,$receivedJson['steamTicket'],$receivedJson['steamId'],$receivedJson['steamUsername'],$receivedJson['gameId']);
                break;
            }
            case "VerifyModList":
            {
                global $CLIENT_VERSION;
                $verification = verifyModList($receivedJson['clientModList'],$receivedJson['steamId']);
                $message = new ValidateModlist($verification,$CLIENT_VERSION);
                $em = new EncapsulatedMessage("ModVerificationResponse",json_encode($message));
                sendEncodedMessage($em,$connection);
                break;
            }
            default: {logWarn("Unknown message: ".$receivedJson['messageType']); break;}
        }
    }
    catch(Exception $e)
    {
        logError("Timed out while trying to process message " . $receivedJson["messageType"]);
        logError($e->getMessage());
    }
    finally
    {
        set_time_limit(0);
    }

}
?>