<?php

namespace App;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Redis;
use Log;
use Cassandra;

class VoteService extends ServiceProvider {

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

        try {
            $cached = Redis::hmget("vote_counts:$postid", 'upvote', 'downvote');

            //Log::info($cached);

            if($cached[0] != null && $cached[1] != null) {

                
                //Log::info("fetch from cache");
                
                return ['success' => true, 'result' => [
                    'upvote' => $cached[0] ,
                    'downvote' => $cached[1]
                ]];
            }
            
        }catch(\Exception $e) {
            Log::debug($e);                
        }


        $cql = "select sum(downvote) as downvote, sum(upvote) as upvote from post_user where postid = '$postid';";
                
        $result = $this->execute_cql($cql);
        
        if($result['success']) {

            try {

                Redis::hmset("vote_counts:$postid", 'upvote', $result['data'][0]['upvote'], 'downvote', $result['data'][0]['downvote'] );
                Redis::expire("vote_counts:$postid", 10);

                //Log::info("cached: vote_counts:$postid");

            } catch(\Exception $e) {
                Log::debug($e);                
            }

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
                        ->withContactPoints('10.164.6.78', '10.165.2.217')
                        ->withPort(9042)
                        ->build();
            $keyspace  = 'ninegag';
            $session   = $cluster->connect($keyspace);        // create session, optionally scoped to a keyspace
            $statement = new Cassandra\SimpleStatement($cql);

            $result  = $session->execute($statement);  // fully asynchronous and easy parallel execution

            return ['success' => true, 'data' => $result];

        } 
        
        catch (Cassandra\Exception $e) {

            Log::error("Caught exception: " .get_class($e));

            return ['success' => false, 'error' => get_class($e) ];
        }
    }
}