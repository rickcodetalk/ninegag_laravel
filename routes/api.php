<?php

use Illuminate\Http\Request;
use App\VoteService;
//use App\Http\Controllers\VoteController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::get('/vote-status/{userid}', function ($userid) {
    
    $voteService = new VoteService();

    return $voteService->get_vote_status($userid);

});

Route::get('/vote-counts/{postid}', function ($postid) {
    
    $voteService = new VoteService();

    return $voteService->get_vote_counts($postid);

});

Route::post('/vote', function (Request $request) {
    
    $voteService = new VoteService();

    return $voteService->vote($request->postid, $request->userid, 
        ($request->score == 1) ? 1 : 0, 
        ($request->score == -1) ? 1 : 0);

});