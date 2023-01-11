<?php
namespace PokerMsg;
use DateTime;
use Exception;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class MsgServer implements MessageComponentInterface {
    protected $users;
    protected $user_cons;
    protected $user_subs;

    public function __construct() {
        $this->logMsg("Starting up MsgServer");
        $this->users = Array();
        $this->user_cons = Array();
        $this->user_subs = Array();
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->users[$conn->resourceId] = 1;
        $this->logMsg("New connection from $conn->resourceId established.");
        $this->logActiveUsers();
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $msg = trim($msg);
        $this->logMsg("New message from $from->resourceId is: $msg");
        if (str_contains($msg, 'subscribe:')) {
            $team = trim(str_replace('subscribe:','', $msg));
            if (strlen($team)>0) {
                $this->logMsg(".. added subscription on team '$team' for $from->resourceId");
                $this->user_cons[$from->resourceId] = $from;
                $this->user_subs[$from->resourceId] = $team;
                $this->logActiveTeams();
            }
        } else if (str_contains($msg, 'push')) {

            if (isset($this->user_subs[$from->resourceId])) {

                $team = $this->user_subs[$from->resourceId];
                $this->logMsg(".. received a push notification to all users of team: $team");

                foreach ($this->user_cons as $user) {
                    if (isset($this->user_subs[$user->resourceId]) and $this->user_subs[$user->resourceId] == $team) {
                        $this->logMsg(".. posting pull request to $user->resourceId");
                        $user->send("pull");
                    }
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        unset($this->user_subs[$conn->resourceId]);
        unset($this->user_cons[$conn->resourceId]);
        unset($this->users[$conn->resourceId]);
        $this->logMsg("Connection {$conn->resourceId} has disconnected.");
        $this->logActiveUsers();
        $this->logActiveTeams();
    }

    public function onError(ConnectionInterface $conn, Exception $e) {
        if (isset($conn->resourceId)) {
            unset($this->user_subs[$conn->resourceId]);
            unset($this->user_cons[$conn->resourceId]);
            unset($this->users[$conn->resourceId]);
        }
        $this->logMsg("An error has occurred: {$e->getMessage()}");
        $conn->close();
    }

    private function logActiveUsers() {
        $num_cons = count($this->users);
        $this->logMsg("active_users=$num_cons");
    }

    private function logActiveTeams() {
        $teams = array();
        foreach ($this->user_subs as $user_sub) {
            $teams[$user_sub] = 1;
        }
        $this->logMsg("active_teams=".count($teams));
    }
    private function logMsg($msg) {
        $date = new DateTime();
        $date = $date->format("Y/m/d H:i:s");
        $delimiter = " ";
        echo $date.$delimiter.trim($msg)."\n";
    }
}