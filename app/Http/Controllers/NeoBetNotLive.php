<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
$stakes = new NeoBetNotLive();
$stakes = $stakes->parse();

/**
 *
 */
const url = 'https://neo.bet/.sportsbet/program/matches?sport=';
/**
 *
 */
const sportList = ['Football', 'Tennis', 'Basketball', 'Icehockey', 'Handball', 'Volleyball', 'Boxing', 'MixedMartialArts', 'AmericanFootball', 'Baseball', 'Motorsport'];

/**
 * Class NeoBet
 * @package App\Http\Controllers
 */
class NeoBetNotLive extends Controller
{
    /**
     *
     */
    public function parse()
    {
        global $finalData;
        $finalData = [];
        foreach (sportList as $sport) {
            $url = url . $sport;
            $collection = json_decode(file_get_contents($url));
            $finalData = array_merge($finalData, $this->parseMatches($collection, $finalData));
        }
        dd($finalData);
        return $finalData;
    }

    /**
     * @param $matches
     * @param $finalData
     * @return array
     */
    private function parseMatches($matches, $finalData)
    {
        $finalArray = [];
        foreach ($matches as $match) {
            if ($match->contestStatus->name !== 'Running') {
                $matchId = $match->id;
                foreach ($finalData as $data) {
                    if ($matchId === $data['id']) continue 2;
                }
                $finalArray[] = $this->transform($match);
            }
        }
        return $finalArray;
    }

    /**
     * @param $match
     * @return array
     */
    private function transform($match)
    {
        $objDataHora = date_create($match->begin);
        date_add($objDataHora, date_interval_create_from_date_string('-1 hour'));
        $objDataHora = $objDataHora->format('Y-m-d H:i:s');
        $transformedMatch = [
            'id' => $match->id,
            'team1' => $match->home,
            'team2' => $match->away,
            'datetime' => $objDataHora,
            'sport' => $this->getSportLabel($match),
            'tourney' => $match->regionLabel . '. ' . $match->league,
            'isLive' => '0',
        ];
        $transformedMatch = array_merge($transformedMatch, $this->transformedBets($match->betmarkets));
        return $transformedMatch;
    }

    /**
     * if $match got no 'sportLabel' field - it will take string from 'sport' field
     * @param $match
     * @return mixed
     */
    private function getSportLabel($match)
    {
        if (!property_exists($match, 'sportLabel')) {
            return $match->sport;
        }
        return $match->sportLabel;
    }

    /**
     * @param $betMarkets
     * @return array|mixed|void
     */
    private function transformedBets($betMarkets)
    {
        $bets = [];
        foreach ($betMarkets as $bet) {
            if ($bet->bettingType === 'MatchWin') {
                (count($bet->odds) === 2) ? $bets += $this->twoWinBet($bet) : $bets += $this->threeWinBet($bet);
            } else if ($bet->bettingType === 'Spread' && is_int($bet->handicap)) {
                $bets += $this->foraBet($bet);
            } else if ($bet->bettingType === 'OverUnder') {
                $bets += $this->totalsBet($bet);
            }
        }
        return $bets;
    }

    /**
     * @param $bet
     * @return mixed
     */
    private function twoWinBet($bet)
    {
        $betData['2w'][0] = [];
        foreach ($bet->odds as $odd) {
            if ($odd->outcome === 'Home') {
                $betData['2w'][0] += [
                    '1' => $odd->odds
                ];
            } else if ($odd->outcome === 'Away') {
                $betData['2w'][0] += [
                    '2' => $odd->odds
                ];
            }
        }
        return $betData;
    }

    /**
     * @param $bet
     * @return mixed
     */
    private function threeWinBet($bet)
    {
        $betData['3w'][0] = [];
        foreach ($bet->odds as $odd) {
            if ($odd->outcome === 'Home') {
                $betData['3w'][0] += [
                    '1' => $odd->odds
                ];
            } else if ($odd->outcome === 'Away') {
                $betData['3w'][0] += [
                    '2' => $odd->odds
                ];
            } else if ($odd->outcome === 'Draw') {
                $betData['3w'][0] += [
                    'x' => $odd->odds
                ];
            }
        }
        return $betData;
    }


    /**
     * also checks if foraBet is an integer value (whole number)
     * @param $bet
     * @return mixed
     */
    private function foraBet($bet)
    {
        $betData['eh'][0] = [];
        if (is_int($bet->handicap)) {
            $betData['eh'][0] = $this->getForaBets($bet);
        }
        return $betData;
    }

    /**
     * @param $bet
     * @return mixed
     */
    private function getForaBets($bet)
    {
        $final[$bet->handicap] = [];
        $final[-$bet->handicap] = [];
        $homeAway = null;
        foreach ($bet->odds as $odd) {
            ($odd->outcome === 'Home') ? $homeAway = 1 : $homeAway = 2;
            if ($homeAway === 1) {
                $final[$bet->handicap] += [$homeAway => $odd->odds];
            } elseif ($homeAway === 2) {
                $final[-$bet->handicap] += [$homeAway => $odd->odds];
            }

        }
        return $final;
    }

    /**
     * @param $bet
     * @return mixed
     */
    private function totalsBet($bet)
    {
        $totalsBetData['totals'][0][strval($bet->boundary)] = [];
        foreach ($bet->odds as $odd) {
            $totalsBetData['totals'][0][strval($bet->boundary)] += [strtolower($odd->outcome) => $odd->odds];
        }
        return ($totalsBetData);
    }
}
