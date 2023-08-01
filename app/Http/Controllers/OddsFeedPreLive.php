<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class OddsFeedPreLive extends Controller
{
    protected $gamesBySport, $sportList, $leagueList, $countryList, $oddList, $marketsList, $marketTypes;

    public function __construct()
    {
        $this->loadInitialData();
    }

    public function loadInitialData()
    {
        $this->gamesBySport = $this->getGamesList();
        $this->sportList = $this->getSportsList();
        $this->leagueList = $this->getLeaguesList();
        $this->countryList = $this->getCountriesList();
        $this->oddList = $this->getOddsList();
        $this->marketsList = $this->getMarketsList();
        $this->marketTypes = $this->getMarketTypes();
//        dd($this->marketsList[1], $this->oddList);
//        $this->che();
    }

    public function che()
    {
//        dd($this->oddList);
        foreach ($this->oddList as $odd) {
            foreach ($odd->Markets as $market) {
                if ($market->MarketTypeID !== 1 && $market->MarketTypeID !== 390001) dd($market);
            }
        }
    }

    public function main()
    {
        $final = [];
        foreach ($this->gamesBySport as $gamesByOneSport) {
            foreach ($gamesByOneSport->Leagues as $leagueOfSport) {
                foreach ($leagueOfSport->Games as $game) {
                    if (!isset($game->Away)) {
                        continue;
                    }
                    if (count($this->gameTransformer($game)) === 7) $final[] = $this->gameTransformer($game);
                }
            }
        }
        dd($final);
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
        curl_setopt($ch, CURLOPT_POST, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($data);
        return $data;
    }

    public function getCountriesList()
    {
        $countryList = $this->getRequestFunction('countries')->Countries;
        return $countryList;
    }

    public function getSportsList()
    {
        $sportList = $this->getRequestFunction('sports')->Sports;
        return $sportList;
    }

    public function getMarketTypes()
    {
        $marketTypes = $this->getRequestFunction('markettypes')->MarketTypes;
        return $marketTypes;
    }

    public function getLeaguesList()
    {
        $leaguesList = $this->getRequestFunction('leagues')->Sports;
        return $leaguesList;
    }

    public function getGamesList()
    {
        $gamesList = $this->postRequestFunction('games')->Sports;
        dd($this->postRequestFunction('games')->Sports);
        return $gamesList;
    }

    public function getMarketsList()
    {
        $marketsList = $this->postRequestFunction('markets')->Sports;
        return $marketsList;
    }

    public function getOddsList()
    {
        $oddsList = $this->postRequestFunction('odds')->Odds;
        return $oddsList;
    }


    public function gameTransformer($game)
    {
        $objDataHora = date_create($game->GameDate);
        $objDataHora = $objDataHora->format('Y-m-d H:i:s');
        $transformedMatch = [
            'team1' => $game->Home,
            'team2' => $game->Away,
            'datetime' => $objDataHora,
            'sport' => $this->getSport($game->SportID),
            'tourney' => $this->getTourney($game->SportID, $game->LeagueID),
            'isLive' => '0',
        ];
        $transformedGameMarkets = $this->collectGameMarkets($game->GameID);

        if (!empty($transformedGameMarkets)) {
            $transformedMatch = array_merge($transformedMatch, $transformedGameMarkets);
        }
        return $transformedMatch;
    }

    public function getSport($sportId)
    {
        foreach ($this->sportList as $sport) {
            if ($sport->SportID === $sportId)
                return $sport->SportName;
        }
    }

    public function getTourney($sportId, $leagueId)
    {
        foreach ($this->leagueList as $leaguesBySport) {
            if ($leaguesBySport->SportID === $sportId) {
                foreach ($leaguesBySport->Leagues as $league) {
                    if ($league->LeagueID === $leagueId) {
                        $tourney = $this->getCountry($league->CountryID) . ', ' . $league->LeagueName;
                    }
                }
            }
        }
        return $tourney;
    }

    public function getCountry($countryID)
    {
        foreach ($this->countryList as $country) {
            if ($country->CountryID === $countryID) {
                return $country->CountryCode;
            }
        }
    }

    //кэфы в $oddList->$odd->Markets связь по LineIntID
    //инфа в $marketsCollection связь по LineIntID
    //назваания маркетов в $this->marketTypes
    //+"MarketTypeID": 1540013
    //+"MarketTypeName": "Number Of Team Goals"
    //+"EventTypeName": "Number Of Team Goals"

    public function collectGameMarkets($gameId)
    {
        $gameMarkets = [];
        foreach ($this->marketsList as $marketsBySport) {
            foreach ($marketsBySport->Leagues as $marketsByleague) {
                foreach ($marketsByleague->Games as $marketsByGame) {
                    if ($marketsByGame->GameID === $gameId) {
                        foreach ($marketsByGame->Markets as $market) {
                            if ($this->checkMarketDontNeeded($market->MarketTypeID)) {
                                continue;
                            }
//                            $marketTypeName = $this->getMarketTypeNameHelper($market->MarketTypeID); /*название ставки*/
//                            $gameMarkets[] = $marketTypeName;
                            $odds = $this->getMarketOdds($gameId, $market->MarketTypeID);
                            $markets = $market->Lines;
//                            dd($marketTypeName, $market, $gameMarkets, $odds);
//                            dd($this->oddList);
//                            dd('$market', $markets);
                            $marketName = count($odds) . 'w'; //2w-3w
                            if ($marketName === '2w') $gameMarkets[$marketName][0] = $this->getFinalCoeffs2w($odds, $markets);
                            if ($marketName === '3w') $gameMarkets[$marketName][0] = $this->getFinalCoeffs3w($odds, $markets);
//                            if (empty($gameMarkets)) dd('$marketsByGame', $marketsByGame, $gameMarkets, '$marketName', $marketName);
                        }
//                        dd($marketsByGame);
                    }
                }
            }
        }
//        if (empty($gameMarkets)) dd('$marketsByGame', $marketsByGame);
        return $gameMarkets;
    }

    public function getFinalCoeffs2w($odds, $markets)
    {
        $data = [];
        foreach ($markets as $market) {
            foreach ($odds as $odd) {
                if ($market->LineIntID === $odd->LineIntID) {
                    $data += [
                        $market->RowTypeID => $odd->Odds
                    ];
                }
            }
        }
        if (isset($data[3])) {
            $data[2] = $data[3];
            unset($data[3]);
        }
//        $data[2] = $data[3];

        return $data;
    }

    public function getFinalCoeffs3w($odds, $markets)
    {
        $data = [];
        foreach ($markets as $market) {
            foreach ($odds as $odd) {
                if ($market->LineIntID === $odd->LineIntID) {
                    if ($market->LineName === 'Draw') {
                        $data += ['x' => $odd->Odds];
                        continue;
                    }
                    $data += [
                        $market->RowTypeID => $odd->Odds
                    ];
                }
            }
        }
        $data[2] = $data[3];
        unset($data[3]);
        return $data;
    }

    public function checkMarketDontNeeded($marketTypeId)
    {
        $neededMarkets = [
            390001, // 1X2, Winner
            1, //1x2
        ];
        /** УДАЛИТЬ ПОСЛЕ!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
        $notNeededMarkets = [];
        /** УДАЛИТЬ ПОСЛЕ!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/
        foreach ($neededMarkets as $neededMarket) {
            if ($neededMarket === $marketTypeId) return false;
        }
        dd('checkMarketDontNeeded ne srabotal', $marketTypeId);
        return true;
    }

    public function getMarketTypeNameHelper($MarketTypeID)
    {
        foreach ($this->marketTypes as $marketType) {
            if ($marketType->MarketTypeID === $MarketTypeID) return $marketType->MarketTypeName;
        }
    }

    public function getMarketOdds($gameId, $marketTypeId)
    {
        $odds = [];
        foreach ($this->oddList as $gameOdds) {
            if ($gameOdds->GameID === $gameId) {
//                dd('$gameOdds', $gameOdds);
                foreach ($gameOdds->Markets as $market) {
                    if ($market->MarketTypeID === $marketTypeId) {
                        foreach ($market->Lines as $marketLine) {
                            $odds[] = $marketLine;
                        }
//                        dd('$market->MarketTypeID', $market, '$odds', $odds);
                    }
                }
            }
        }
        return $odds;
    }
}
