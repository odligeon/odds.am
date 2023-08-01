<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OddsFeed extends Controller
{
    public function main()
    {
        $url = 'https://oddsfeed-10betcom.sbtech.com/api/v1/countries';
//        $url = 'https://oddsfeed-10betcom.sbtech.com/api/v1/odds';
        $proxy = '217.23.5.76:7283';
        $proxyauth = 'oddsamproxy:s5l82f7D7d';
//
//        $data = ['IsLive' => 1];
//        $data_string = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        $contents = curl_exec($ch);
        curl_close($ch);
        $contents = json_decode($contents);

        dd($contents);
//        dd($contents->Odds[666]);
//        dd($contents->Sports[0]->Leagues[0]->Games);
    }

    public function getRequestFunction($endPoint)
    {
        $url = 'https://oddsfeed-10betcom.sbtech.com/api/v1/' . $endPoint;
        $proxy = '217.23.5.76:7283';
        $proxyauth = 'oddsamproxy:s5l82f7D7d';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($data);
        dd($data);
        return $data;
    }

    public function postRequestFunction($endPoint)
    {
        $url = 'https://oddsfeed-10betcom.sbtech.com/api/v1/' . $endPoint;
        $proxy = '217.23.5.76:7283';
        $proxyauth = 'oddsamproxy:s5l82f7D7d';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch,CURLOPT_POST, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($data);
        return $data;
    }

    public function getCountriesList()
    {
        $countyList = $this->getRequestFunction('countries')->Countries;
        dd($countyList);
        //259
//        +"CountryID": 3
//        +"CountryCode": "Afghanistan"
//        +"CountryName": "AF"
    }

    public function getSportsList()
    {
        $sportList = $this->getRequestFunction('sports')->Sports;
        dd($sportList);
//        +"SportID": 3
//        +"SportName": "Football"
    }

    public function getMarketTypes()
    {
        $marketTypes = $this->getRequestFunction('markettypes')->MarketTypes;
        dd($marketTypes);
//        +"MarketTypeID": 2950013
//        +"MarketTypeName": "Goal Scored Starting 2nd Half-60 Min"
//        +"EventTypeName": "Goal Scored Starting 2nd Half-60 Min"
//        +"LineTypeName": ""
//        +"EventTypeID": 295
//        +"LineTypeID": 13
//        +"IsQA": 1
    }

    public function getLeaguesList()
    {
        $leaguesList = $this->getRequestFunction('leagues')->Sports;
        dd($leaguesList);
//        array:31 [▼
//         0 => {#272 ▼
//        +"SportID": 3
//        +"SportName": "Football"
//        +"Leagues": array:2 [▼
//          0 => {#266 ▼
//            +"LeagueID": 88808
//            +"LeagueName": "NFL"
//            +"RankID": 33
//            +"CountryID": 227
//      }
    }

    public function getGamesList()
    {
        $gamesList = $this->postRequestFunction('games')->Sports;
        dd($gamesList);
//        array:21 [▼
//  0 => {#272 ▼
//        +"SportID": 1
//        +"Leagues": array:62 [▼
//      0 => {#266 ▼
//            +"LeagueID": 22054
//            +"Games": array:2 [▼
//          0 => {#273 ▼
//                +"GameID": 21095070
//                +"Home": "Tigre"
//                +"Away": "Defensores de Belgrano"
//                +"GameDate": "2020-12-29T22:20:00"
//                +"IsLive": 0
//                +"GameType": 0
//                +"LeagueID": 22054
//                +"SportID": 1
    }

    public function getMarketsList()
    {
        $marketsList = $this->postRequestFunction('markets')->Sports;
        dd($marketsList);
    }

    public function getOddsList()
    {
        $oddsList = $this->postRequestFunction('odds');
        dd($oddsList->Odds);
    }
}
