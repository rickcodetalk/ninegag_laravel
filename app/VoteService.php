<?php

namespace App;

use Illuminate\Support\ServiceProvider;
use Log;
use Cassandra;

class voteService extends ServiceProvider {

    public function __construct()
    {
    }

    public function vote($postid, $userid, $upvote, $downvote) {
        
        $cql = "insert into post_user (postid, userid, upvote, downvote, timestamp) values ('$postid', '$userid', $upvote, $downvote, dateOf(now()));";
                
        $result = $this->execute_cql($cql);
        
        if($result['success']) {

            return ['success' => true];
        } else {

            return ['success' => false];
        }
    }

    public function get_vote_status($userId) {
        
        $cql = "select * from user_timestamp where userid = '$userId' limit 100;";
                
        $result = $this->execute_cql($cql);
        
        if($result['success']) {

            $arr = array();
            foreach ($result['data'] as $row) {
                
                array_push($arr, array(
                    'postid' => $row['postid'],
                    'score' => $row['upvote'] + -1 * $row['downvote']
                    ));
            }

            return ['success' => true, 'result' => $arr];
        } else {

            return ['success' => false];
        }
    }

    public function get_vote_counts($postid) {
        
        $cql = "select sum(downvote) as downvote, sum(upvote) as upvote from post_user where postid = '$postid';";
                
        $result = $this->execute_cql($cql);
        
        if($result['success']) {

            Log::debug($result['data'][0]);
             

            return ['success' => true, 'result' => [
                'upvote' => $result['data'][0]['upvote'],
                'downvote' => $result['data'][0]['downvote']
                ]];
        } else {

            return ['success' => false];
        }
    }

    function execute_cql($cql) {
        try {
            $cluster   = Cassandra::cluster()                 // connects to localhost by default
                        ->build();
            $keyspace  = 'ninegag';
            $session   = $cluster->connect($keyspace);        // create session, optionally scoped to a keyspace
            $statement = new Cassandra\SimpleStatement($cql);

            $result  = $session->execute($statement);  // fully asynchronous and easy parallel execution

            return ['success' => true, 'data' => $result];

        } catch (Cassandra\Exception $e) {

            Log::error("Caught exception: " .get_class($e));

            return json_encode(['success' => false, 'error' => get_class($e) ]);
        }
    }
}