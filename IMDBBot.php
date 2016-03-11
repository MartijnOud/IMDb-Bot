<?php
/**
 * API wrapper for Open Movie Database API (http://omdbapi.com/)
 * with responses for Slack slash commands
 *
 *  @version v1.1.0
 *  @license https://opensource.org/licenses/MIT
 *
 *  require 'IMDBBot.php';
 *  use MartijnOud\IMDBBot;
 *  $IMDBBot = new IMDBBot();
 *  echo json_encode($IMDb->q('Shawshank redemption'));
 *
 */

namespace MartijnOud;
class IMDBBot
{

    /**
     * Uses the main OMDB API (?i= or ?t=)
     * Returns a single result with detailed info
     * @param  string $q Movie title or imdb ID
     * @return array with Slackable response
     */
    public function q($q)
    {

        // Make URL proof
        $q = urlencode($q);

        // Call API
        // Check if imdb ID or title
        if (preg_match("/tt\\d{7}/", $q)) {
            $item = $this->call('http://omdbapi.com/?i='.$q);
        } else {
            $item = $this->call('http://omdbapi.com/?t='.$q);
        }

        if ($item->Response != "True") {

            $payload = array(
                "response_type" => "in_channel",
                "text" => "Sorry I didn't find anything!",
            );

        } else {

            // Prepare response
            $payload = array(
                "response_type" => "in_channel",
                "text" => "This is what I've found for _".urldecode($q)."_:",
                "attachments" => array(
                    array(
                        "title" => $item->Title . " (".$item->Year.")",
                        "title_link" => "http://www.imdb.com/title/".$item->imdbID."/",
                        "thumb_url" => ($item->Poster != "N/A" ? $item->Poster : null),
                        "text" => $item->Plot,

                        "fields" => array(
                            array(
                                "title" => "Released",
                                "value" => $item->Released,
                                "short" => true,
                            ),

                            array(
                                "title" => "Runtime",
                                "value" => $item->Runtime,
                                "short" => true,
                            ),

                            array(
                                "title" => "Actors",
                                "value" => $item->Actors,
                                "short" => true,
                            ),

                            array(
                                "title" => "Rating",
                                "value" => ($item->imdbRating != "N/A" ? $item->imdbRating . "/10" : "N/A") . ($item->imdbVotes != "N/A" ? " (" . $item->imdbVotes . ")" : ""),
                                "short" => true,
                            ),

                        ),
                    ),
                ),
            );

        }


        return $payload;

    }

    /**
     * UNUSED.
     * Uses the search OMDB API (?s=)
     * Returns multiple results with limited info
     * @param  string $q     Search parameter
     * @param  int    $limit Maximum number of responses to return
     * @return array with Slackable response
     */
    public function search($q, $limit = 5)
    {

        // Make URL proof
        $q = urlencode($q);

        // Call API
        $items = $this->call('http://omdbapi.com/?s='.$q)->Search;

        if (empty($items)) {

            $payload = array(
                "response_type" => "in_channel",
                "text" => "Sorry I didn't find anything!",
            );

        } else {

            // Prepare response
            $payload = array(
                "response_type" => "in_channel",
                "text" => "This is what I've found for _".urldecode($q)."_:",
            );

            // Add results
            foreach ($items as $item) {

                if ($limit > $i) {

                    $payload['attachments'][] = array(
                        "title" => $item->Title . " (".$item->Year.")",
                        "title_link" => "http://www.imdb.com/title/".$item->imdbID."/",
                        "thumb_url" => ($item->Poster != "N/A" ? $item->Poster : null),
                    );


                    $i++;
                }
            }

        }


        return $payload;

    }

    /**
     * Make a curl request to given $url and return json response
     * @param string $search
     * @return json_decoded contents of omdbapi
     */
    private function call($url)
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        return json_decode($response);
    }


}