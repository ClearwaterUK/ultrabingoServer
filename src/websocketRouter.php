<?php

function decodeMessage($message)
{
    return json_decode(base64_decode($message),true);
}

function sendEncodedMessage($messageToSend,$connection):void
{
    try {
        $encodedMessage = base64_encode(json_encode($messageToSend));
        $connection->text($encodedMessage);
    }
    catch(Exception $e)
    {
        logError("Failed to send message to connection!");
        logError($e->getMessage());
        logError($e->getTrace());
    }
}

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
            logWarn("Client who dropped was the host of game ".$gameDetails[0]);
            if(checkPlayerCountOfGame($gameDetails[0]) > 0)
            {
                logWarn("Picking a random other player as host");
                $list = array_filter(array_keys($associatedGame->currentPlayers),function($elem) use($steamId){
                    return $elem != $steamId;
                });
                $newHost = $list[array_rand($list)];

                $associatedGame->gameHost = $newHost;
                updateHostForGame($gameDetails[0],$newHost);

                logWarn($associatedGame->currentPlayers[$newHost]->username." is now the new host for game ".$gameDetails[0]);
                logWarn("Notifying all players in game");

                $message = new NewHostNotification($username,$associatedGame->currentPlayers[$newHost]->username,$newHost);
                $em = new EncapsulatedMessage("NewHostNotification",json_encode($message));

                foreach($associatedGame->currentPlayers as $playerSteamId => $playerObj)
                {
                    if($playerSteamId != $steamId)
                    {
                        sendEncodedMessage($em,$playerObj->websocketConnection);
                    }
                }
            }
            else
            {
                logWarn("No other players in game - destroying game!");
                $gameCoordinator->disconnectAllPlayers($gameDetails[0],$connection,"HOSTDROPPED");
                $gameCoordinator->destroyGame($gameDetails[0]);
                removeGame($gameDetails[0]);
            }
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
            //unset($associatedGame->currentPlayers[$indexToUnset]);
        }
        //unregisterConnection($steamId);
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
                $roomData = createRoomInDatabase($receivedJson);
                $roomId = intval($roomData[0]);
                $roomPassword = $roomData[1];
                if($roomId <> null && $roomId <> 0)
                {
                    //Create the room
                    $status = "ok";
                    $game = $gameCoordinator->createGame($roomId,$receivedJson["hostSteamName"],$connection,$receivedJson["hostSteamId"],$receivedJson['rank']);
                    logMessage("Game created and set up with id ".$roomId." , password " . $roomPassword);
                    $crr = new CreateRoomResponse($status,$roomId,$roomPassword,$game);
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
                logMessage($receivedJson['username']." wants to join game with password".$receivedJson['password']);

                //Start by checking if game exists.
                $game = lookForGame($receivedJson['password']);
                if($game <> 0)
                {
                    $gameId = intval($game['R_ID']);
                    $canJoin = checkJoinEligibility($game,$receivedJson['steamId'],explode(":",$connection->getRemoteName())[0]);
                    if($canJoin == 0)
                    {
                        $gameCoordinator->joinGame($gameId,$receivedJson['username'],$receivedJson['steamId'],$connection,$receivedJson['rank']);
                        $crr = new JoinRoomResponse($canJoin,$gameId,$gameCoordinator->currentGames[$gameId]);
                        $em = new EncapsulatedMessage("JoinRoomResponse",json_encode($crr));
                        sendEncodedMessage($em,$connection);
                    }
                    else
                    {
                        $crr = new JoinRoomResponse($canJoin,-1,null);
                        $em = new EncapsulatedMessage("JoinRoomResponse",json_encode($crr));
                        sendEncodedMessage($em,$connection);
                        $connection->close();
                    }
                }
                else
                {
                    $crr = new JoinRoomResponse(-1,-1,null);
                    $em = new EncapsulatedMessage("JoinRoomResponse",json_encode($crr));
                    sendEncodedMessage($em,$connection);
                    $connection->close();
                }
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

            case "ReconnectRequest":
            {
                logMessage("Player requesting reconnection");

                if (!verifyConnection($receivedJson['ticket'])) {
                    logWarn("Invalid Steam ticket or player is not in game, rejecting reconnection");
                    return;
                }

                $game = $gameCoordinator->currentGames[$receivedJson['roomId']];
                if ($game <> null)
                {
                    foreach ($game->currentPlayers as $playerSteamId => $playerObj)
                    {
                        if ($playerObj->steamId === $receivedJson['steamId']) {
                            //Update connection here.
                            $playerObj->websocketConnection = $connection;
                            updateConnection($connection,$playerObj->steamId);

                            logMessage("Sending fresh game data to reconnected player");
                            $message = new ReconnectResponse("OK",$game);
                            $em = new EncapsulatedMessage("ReconnectResponse", json_encode($message));
                            sendEncodedMessage($em, $connection);
                        }
                    }
                }
                else
                {
                    logError("Game no longer exists in coordinator, sending error response");
                    $message = new ReconnectResponse("END",$game);
                    $em = new EncapsulatedMessage("ReconnectResponse", json_encode($message));
                    sendEncodedMessage($em, $connection);
                }
            }

                break;

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
                        $game = $gameCoordinator->currentGames[$gameId];
                        $pos = $receivedJson['row']."-".$receivedJson['column'];

                        //Check if the claimed level is the subject of a reroll vote.
                        //If so, cancel the reroll vote.
                        $mapIsBeingVoted = $game->votePosition == $pos;
                        if($mapIsBeingVoted)
                        {
                            logWarn("CLAIMED MAP IS BEING VOTED ON, CANCELLING VOTE");
                            $game->resetVoteVariables();
                        }

                        //Call onMapClaim to see if the map claim causes the game to end or not.
                        $gameCoordinator->currentGames[$gameId]->gamemode->onMapClaim($gameCoordinator->currentGames[$gameId],$receivedJson,$submitResult,$mapIsBeingVoted);
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

                //Fetch the ranks available for the current SteamID.
                $availableRanks = fetchAvailableRanks($receivedJson['steamId']);
                if($availableRanks == "")
                {
                    logInfo("No ranks for requesting SteamID");
                }


                //Fetch the current message of the day.
                $motd = file_get_contents(__DIR__."/../motd.txt");

                $message = new ValidateModlist($verification,$CLIENT_VERSION,$motd,$availableRanks);

                $em = new EncapsulatedMessage("ModVerificationResponse",json_encode($message));
                sendEncodedMessage($em,$connection);
                break;
            }
            case "FetchGames":
            {
                logWarn("Fetching games");
                $games = getPublicBingoGames();
                $status = "";
                if(count($games) > 0)
                {
                    $status = "ok";
                }
                else
                {
                    $status = "none";
                }

                $message = new FetchGamesResponse($status,json_encode($games));
                $em = new EncapsulatedMessage("FetchGamesResponse",json_encode($message));

                sendEncodedMessage($em,$connection);

                break;
            }
            case "RerollRequest":
            {
                global $voteTimers;
                if(verifyConnection($receivedJson['steamTicket']))
                {
                    $gameId = $receivedJson['gameId'];
                    if(array_key_exists($gameId,$gameCoordinator->currentGames))
                    {
                        $game = $gameCoordinator->currentGames[$gameId];

                        logMessage("Requesting reroll of map at ".$receivedJson['column']."-".$receivedJson['row'] . " in game " . $gameId);

                        //Start by checking if a vote is already active or not.
                        if($game->isVoteActive())
                        {
                            //If vote is active, make sure player hasn't already voted.
                            if(!$game->hasPlayerVoted($receivedJson['steamId']))
                            {
                                $game->addPlayerVote($receivedJson['steamId']);
                            }
                        }
                        else
                        {
                            logMessage("Vote not active in game, starting");
                            //Check if player can start vote.
                            if($game->canPlayerStartVote($receivedJson['steamId']))
                            {
                                $game->startRerollVote($receivedJson['steamId'],$receivedJson['column'],$receivedJson['row']);
                            }
                            else
                            {
                                logWarn("Player can't start vote, or has already used their start vote!");
                            }
                        }
                    }
                }
                break;
            }
            default: {logWarn("Unknown message: ".$receivedJson['messageType']); break;}
        }
    }
    catch(Exception $e)
    {
        logError("Error while trying to process message " . $receivedJson["messageType"]);
        logError($e->getMessage());
        logError($e->getTrace());
    }
    finally
    {
        set_time_limit(0);
    }

}
?>