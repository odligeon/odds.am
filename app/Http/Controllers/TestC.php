<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

/**
 * Class TestC
 * @package App\Http\Controllers
 */
class TestC extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function test()
    {
        $client = new Client();
        $link = 'https://ad.winamax.fr/partners/betting/odds/export.php?sport=1';
        $proxySettings = 'http://oddsamproxy:s5l82f7D7d@217.23.5.76:7283';
        $response = $client->request('GET', $link, ['proxy' => $proxySettings]);
        $content = $response->getBody()->getContents();
        $this->parseResponse($content);
    }

    /**
     * @param $response
     */
    private function parseResponse($response)
    {
        $collection = json_decode(json_encode((array)simplexml_load_string($response)), true);
        $test = [];

        foreach ($collection['List']['Match'] as $index => $record) {
            $test[] = $this->transform($record);
        }
        dd(collect($test)->take(10));
    }

    /**
     * @param $record
     * @return array
     */
    private function transform($record)
    {
        $time = str_replace('T', ' ',$record['@attributes']['Date']);
        $time = (strstr($time, '+', true));
        $transformedRecord = [
            'id' => $record['@attributes']['Id'],
            'team1' => $record['HomeTeam'],
            'team2' => $record['AwayTeam'],
            'datetime' => $time,
            'sport' => $record['Sport']['@attributes']['Name'],
            'tourney' => $record['Tournament']['@attributes']['Name'],
            'isLive' => $record['Live'] ?? 0,
//            'bets' => $record['Bets'],
        ];
//        if (!isset($record['Bets']['Bet'][0]['@attributes']['BetCode'])) {
//            dd($record);
//        }
        $transformedRecord = $this->transformBets($record['Bets']['Bet'], $transformedRecord);
//        dd($transformedRecord);
        return $transformedRecord;
    }

    /**
     * @param $originalBets
     * @param $transformedRecord
     * @return mixed
     */
    private function transformBets($originalBets, $transformedRecord)
    {
        /** Handle Main Game Bets Case */
        if ($this->isMainGameBets($originalBets)) {
            $winData = $this->outcomeCheck($originalBets);
            /** Winkey Is 2W or 3W */
            $transformedRecord[$winData['winKey']] = $winData['betData'];
        }

//        /** Handle Multiple Bets Case, When They Have Totals  */
        if ($this->isGameHasTotals($originalBets)) {
            $transformedRecord['totals'] = $this->getGameTotals($originalBets);
        }

//        foreach ($originalBets as $key => $bet) {
//            if ($this->isTotalBet($bet)) {
//                $transformedRecord['totals'][] =  $this->getTotalBets($bet);}
//        }
//
        return $transformedRecord;
    }


    /**
     * @param $originalBets
     * @return array
     */
    private function getGameTotals($originalBets)
    {
        $totals = [];

        foreach ($originalBets as $key => $bet) {
            if ($key !== 0) {
                $totalInteger = explode('18-total=', $bet['@attributes']['BetCode'])[1];
                $totals[$totalInteger] = $this->getOverUnder($bet);
            }
        }
        return [$totals];
    }

    private function getOverUnder($bet)
    {
        $overUnder = [];
        foreach ($bet['Outcomes'] as $outcome) {
            $overUnder[$outcome['@attributes']['ResultCode']] = $outcome['@attributes']['Odds'];
        }
        return $overUnder;
    }

    /**
     * @param $originalBets
     * @return bool
     */
    private function isMainGameBets($originalBets)
    {
        if (isset($originalBets['@attributes']) || $originalBets[0]['@attributes']['BetCode'] === '1-') {
            return true;
        }
        return false;
    }

    /**
     * @param $originalBets
     * @return bool
     */
    private function isGameHasTotals($originalBets)
    {
        /** Check First Bet If it has 18-total in it's BetCode.
         *  When First Bet Has it, we assume that Other bets also have totals and Totals Can Be calculated
         */
        if (isset($originalBets[1])) {
            return (strpos($originalBets[1]['@attributes']['BetCode'], '18-total') !== false);
        }
        return false;
    }

    private function isTotalBet($bet)
    {
        /** CheckBet If it has 18-total in it's BetCode.
         */
        if (isset($bet['@attributes'])) {
            return (strpos($bet['@attributes']['BetCode'], '18-total') !== false);
        }
        return false;
    }

    /**
     * @param $bet
     * @return array
     */
    private function outcomeCheck($bet)
    {
        $outcomes = isset($bet['Outcomes']) ? $bet['Outcomes'] : $bet[0]['Outcomes'];

        return count($outcomes) === 2 ? $this->getFormattedWinData($outcomes, '2w') : $this->getFormattedWinData($outcomes, '3w');
    }

    /**
     * @param $bet
     * @param $winKey
     * @return array
     */
    private function getFormattedWinData($outcomes, $winKey)
    {
        $betData = [];

        foreach ($outcomes as $outcome) {
            $betData[$outcome['@attributes']['ResultCode']] = $outcome['@attributes']['Odds'];
        }

        return [
            'winKey' => $winKey,
            // 0 index for betdata
            'betData' => [$betData]
        ];
    }

}
