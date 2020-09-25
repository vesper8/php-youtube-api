<?php

namespace Madcoda\Youtube\Tests;

use Madcoda\Youtube\Youtube;

use PHPUnit\Framework\TestCase;

/**
 * Class YoutubeTest
 *
 * @category Youtube
 * @package  Youtube
 * @author   Jason Leung <jason@madcoda.com>
 */
class YoutubeTest extends TestCase
{
    /**
     * @var Youtube
     * @var optionParams
     */
    protected $youtube;
    protected $optionParams;

    public function setUp(): void
    {
        // !!!!! DO NOT USE THIS API KEY FOR PRODUCTION USE !!!!! */
        $TEST_API_KEY = 'AIzaSyDlNBnbhP7G9z_8qunELCJ8012PP3t_c1o';
        // !!!!! THIS KEY WOULD BE REVOKED BY AUTHOR ANYTIME !!!!! */
        $params = array(
            'key' => $TEST_API_KEY,
            'referer' => 'fake-refer',
            'apis' => array(
                'videos.list' => 'https://www.googleapis.com/youtube/v3/videos',
                'search.list' => 'https://www.googleapis.com/youtube/v3/search',
                'channels.list' => 'https://www.googleapis.com/youtube/v3/channels',
                'playlists.list' => 'https://www.googleapis.com/youtube/v3/playlists',
                'playlistItems.list' => 'https://www.googleapis.com/youtube/v3/playlistItems',
                'activities' => 'https://www.googleapis.com/youtube/v3/activities',
            )
        );
        $this->youtube = new Youtube($params);
        $this->optionParams = array(
            'order' => 'title'
        );
    }

    public function tearDown(): void
    {
        $this->youtube = null;
        $this->optionParams = null;
    }

    public function MalFormURLProvider()
    {
        return array(
            array('https://'),
            array('http://www.yuotube.com'),
        );
    }

    public function testConstructorFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->youtube = new Youtube(array());
    }

    public function testConstructorFail2()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->youtube = new Youtube('FAKE API KEY');
    }

    public function testInvalidApiKey()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error 400 Bad Request : keyInvalid');
        $this->youtube = new Youtube(array('key' => 'nonsense'));
        $vID = 'rie-hPVJ7Sw';
        $this->youtube->getVideoInfo($vID);
    }

    public function testGetVideoInfoWithParams()
    {
        $vID = 'rie-hPVJ7Sw';
        $response = $this->youtube->getVideoInfo($vID, ['part' => 'id, snippet, status']);

        $this->assertEquals($vID, $response->id);
        $this->assertNotNull('response');
        $this->assertEquals('youtube#video', $response->kind);
        //add all these assertions here in case the api is changed,
        //we can detect it instantly
        $this->assertObjectHasAttribute('status', $response);
        $this->assertObjectHasAttribute('snippet', $response);
        $this->assertObjectNotHasAttribute('contentDetails', $response);
        $this->assertObjectNotHasAttribute('statistics', $response);
    }

    public function testGetVideoInfo()
    {
        $vID = 'rie-hPVJ7Sw';
        $response = $this->youtube->getVideoInfo($vID);

        $this->assertEquals($vID, $response->id);
        $this->assertNotNull('response');
        $this->assertEquals('youtube#video', $response->kind);
        //add all these assertions here in case the api is changed,
        //we can detect it instantly
        $this->assertObjectHasAttribute('statistics', $response);
        $this->assertObjectHasAttribute('status', $response);
        $this->assertObjectHasAttribute('snippet', $response);
        $this->assertObjectHasAttribute('contentDetails', $response);
    }

    public function testGetVideosInfo()
    {
        $vID = array('rie-hPVJ7Sw', 'lRRk97FYLJM');
        $response = $this->youtube->getVideosInfo($vID);
        $this->assertIsArray($response);

        foreach ($response as $value) {
            $this->assertContains($value->id, $vID);
            $this->assertEquals('youtube#video', $value->kind);
            //add all these assertions here in case the api is changed,
            //we can detect it instantly
            $this->assertObjectHasAttribute('statistics', $value);
            $this->assertObjectHasAttribute('status', $value);
            $this->assertObjectHasAttribute('snippet', $value);
            $this->assertObjectHasAttribute('contentDetails', $value);
        }
    }

    public function testSearch()
    {
        $limit = rand(3, 10);
        $response = $this->youtube->search('Android', $limit);
        $this->assertEquals($limit, count($response));
        $this->assertEquals('youtube#searchResult', $response[0]->kind);
    }

    public function testSearchVideos()
    {
        $limit = rand(3, 10);
        $response = $this->youtube->searchVideos('Android', $limit, 'title');
        $this->assertEquals($limit, count($response));
        $this->assertEquals('youtube#searchResult', $response[0]->kind);
        $this->assertEquals('youtube#video', $response[0]->id->kind);
    }

    public function testSearchChannelVideos()
    {
        $limit = rand(3, 10);
        $response = $this->youtube->searchChannelVideos('Android', 'UCVHFbqXqoYvEWM1Ddxl0QDg', $limit, 'title');
        $this->assertEquals($limit, count($response));
        $this->assertEquals('youtube#searchResult', $response[0]->kind);
        $this->assertEquals('youtube#video', $response[0]->id->kind);
    }

    public function testSearchChannelLiveStream()
    {
        $limit = rand(3, 10);
        $expectCount = 1;
        $response = $this->youtube->searchChannelLiveStream('東森', 'UCR3asjvr_WAaxwJYEDV_Bfw', $limit, 'title');
        $this->assertEquals($expectCount, count($response));
        $this->assertEquals('youtube#searchResult', $response[0]->kind);
        $this->assertEquals('youtube#video', $response[0]->id->kind);
    }

    public function testSearchAdvanced()
    {
        $limit = rand(3, 10);
        $params = array(
            'q' => 'Android',
            'type' => 'video',
            'channelId' => 'UCVHFbqXqoYvEWM1Ddxl0QDg',
            'part' => 'id, snippet',
            'order' => 'title',
            'maxResults' => $limit
        );
        $response = $this->youtube->searchAdvanced($params, true);
        $this->assertEquals('youtube#searchResult', $response['results'][0]->kind);
        $this->assertEquals('youtube#searchListResponse', $response['info']['kind']);
    }

    public function testSearchAdvancedWithException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $limit = rand(3, 10);
        $params = array();
        $response = $this->youtube->searchAdvanced($params, true);
    }

    public function testPaginateResults()
    {
        $limit = rand(3, 10);
        $params = array(
            'q' => 'Android',
            'type' => 'video',
            'channelId' => 'UCVHFbqXqoYvEWM1Ddxl0QDg',
            'part' => 'id, snippet',
            'order' => 'title',
            'maxResults' => $limit
        );
        $response = $this->youtube->searchAdvanced($params, true);
        $nextPageToken = $response['info']['nextPageToken'];
        $response = $this->youtube->paginateResults($params, $nextPageToken);
        $this->assertEquals('youtube#searchResult', $response['results'][0]->kind);
        $this->assertEquals('youtube#searchListResponse', $response['info']['kind']);
    }

    public function testGetChannelByName()
    {
        $response = $this->youtube->getChannelByName('Google', $this->optionParams);

        $this->assertEquals('youtube#channel', $response->kind);
        //This is not a safe Assertion because the name can change, but include it anyway
        $this->assertEquals('Google', $response->snippet->title);
        //add all these assertions here in case the api is changed,
        //we can detect it instantly
        $this->assertObjectHasAttribute('snippet', $response);
        $this->assertObjectHasAttribute('contentDetails', $response);
        $this->assertObjectHasAttribute('statistics', $response);
    }

    public function testGetChannelById()
    {
        $channelId = 'UCk1SpWNzOs4MYmr0uICEntg';
        $response = $this->youtube->getChannelById($channelId, $this->optionParams);

        $this->assertEquals('youtube#channel', $response->kind);
        $this->assertEquals($channelId, $response->id);
        $this->assertObjectHasAttribute('snippet', $response);
        $this->assertObjectHasAttribute('contentDetails', $response);
        $this->assertObjectHasAttribute('statistics', $response);
    }

    public function testGetChannelsById()
    {
        $channels = array('UCk1SpWNzOs4MYmr0uICEntg', 'UCK8sQmJBp8GCxrOtXWBpyEA');
        $response = $this->youtube->getChannelsById($channels, $this->optionParams);

        $this->assertTrue(count($response) === 2);
        $this->assertEquals('youtube#channel', $response[0]->kind);
        $this->assertObjectHasAttribute('snippet', $response[0]);
        $this->assertObjectHasAttribute('contentDetails', $response[0]);
        $this->assertObjectHasAttribute('statistics', $response[0]);
    }

    public function testGetPlaylistsByChannelIdAdvanced()
    {
        $playlistParams = [
            'channelId' => $GOOGLE_CHANNELID = 'UCK8sQmJBp8GCxrOtXWBpyEA',
            'maxResults' => 15,
            'part' => 'id,contentDetails,localizations,player,snippet,status',
        ];

        $response = $this->youtube->getPlaylistsByChannelIdAdvanced($playlistParams, true);
        $this->assertGreaterThan(10, $response['results']);
        $this->assertEquals('youtube#playlist', $response["results"][0]->kind);
        $this->assertEquals('Google', $response["results"][0]->snippet->channelTitle);
        $this->assertObjectHasAttribute('snippet', $response["results"][0]);
        $this->assertObjectHasAttribute('contentDetails', $response["results"][0]);

        $this->assertEqualsCanonicalizing(['results', 'info'], array_keys($response));
        $this->assertContains(
            'PL590L5WQmH8e3dS9CtvRofb0nfdGb-Of9',
            array_map(function ($playlist) {
                return $playlist->id;
            }, $response['results'])
        );
        $this->assertNotNull($response["info"]["nextPageToken"]);

        // running another test with pageToken (next page token)
        $playlistParams["pageToken"] = $response["info"]["nextPageToken"];
        $response = $this->youtube->getPlaylistsByChannelIdAdvanced($playlistParams, true);
        $this->assertEqualsCanonicalizing(['results', 'info'], array_keys($response));
        $this->assertGreaterThan(10, $response['results']);
    }


    public function testGetPlaylistsByChannelId()
    {
        $GOOGLE_CHANNELID = 'UCK8sQmJBp8GCxrOtXWBpyEA';
        $response = $this->youtube->getPlaylistsByChannelId($GOOGLE_CHANNELID, $this->optionParams);

        $this->assertTrue(count($response) > 0);
        $this->assertEquals('youtube#playlist', $response[0]->kind);
        $this->assertEquals('Google', $response[0]->snippet->channelTitle);
    }

    public function testGetPlaylistById()
    {
        //get one of the playlist
        $GOOGLE_CHANNELID = 'UCK8sQmJBp8GCxrOtXWBpyEA';
        $response = $this->youtube->getPlaylistsByChannelId($GOOGLE_CHANNELID);
        $playlist = $response[0];

        $response = $this->youtube->getPlaylistById($playlist->id);
        $this->assertEquals('youtube#playlist', $response->kind);
    }

    public function testGetPlaylistItemsByPlaylistId()
    {
        $GOOGLE_ZEITGEIST_PLAYLIST = 'PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs';
        $response = $this->youtube->getPlaylistItemsByPlaylistId($GOOGLE_ZEITGEIST_PLAYLIST);

        $this->assertTrue(count($response) > 0);
        $this->assertEquals('youtube#playlistItem', $response[0]->kind);
    }

    public function testGetPlaylistItemsByPlaylistIdAdvanced()
    {
        $GOOGLE_ZEITGEIST_PLAYLIST = 'PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs';
        $params = array(
            'playlistId' => $GOOGLE_ZEITGEIST_PLAYLIST,
            'part' => 'id, snippet'
        );
        $response = $this->youtube->getPlaylistItemsByPlaylistIdAdvanced($params, true);
        $this->assertEquals('youtube#playlistItem', $response['results'][0]->kind);
        $this->assertEquals('youtube#playlistItemListResponse', $response['info']['kind']);
    }

    public function testGetPlaylistItemsByPlaylistIdAdvancedWithException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $response = $this->youtube->getPlaylistItemsByPlaylistIdAdvanced(null, true);
    }

    public function testParseVIdFromURLFull()
    {
        $vId = $this->youtube->parseVIdFromURL('http://www.youtube.com/watch?v=1FJHYqE0RDg');
        $this->assertEquals('1FJHYqE0RDg', $vId);
    }

    public function testParseVIdFromURLFullWithEmbed()
    {
        $vId = $this->youtube->parseVIdFromURL('https://www.youtube.com/embed/_vA5PvDfNTs');
        $this->assertEquals('_vA5PvDfNTs', $vId);
    }

    public function testParseVIdFromURLShort()
    {
        $vId = $this->youtube->parseVIdFromURL('http://youtu.be/1FJHYqE0RDg');
        $this->assertEquals('1FJHYqE0RDg', $vId);
    }

    /**
     *
     * @dataProvider MalFormURLProvider
     */
    public function testParseVIdFromURLException($url)
    {
        $this->expectException(\InvalidArgumentException::class);
        $vId = $this->youtube->parseVIdFromURL($url);
    }

    public function testParseVIdException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $vId = $this->youtube->parseVIdFromURL('http://www.facebook.com');
    }

    public function testGetActivitiesByChannelId()
    {
        $GOOGLE_CHANNELID = 'UCK8sQmJBp8GCxrOtXWBpyEA';
        $response = $this->youtube->getActivitiesByChannelId($GOOGLE_CHANNELID);
        $this->assertTrue(count($response) > 0);
        $this->assertEquals('youtube#activity', $response[0]->kind);
        $this->assertEquals('Google', $response[0]->snippet->channelTitle);
    }

    public function testGetActivitiesByChannelIdException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $channelId = '';
        $response = $this->youtube->getActivitiesByChannelId($channelId);
    }

    public function testGetChannelFromURL()
    {
        $channel = $this->youtube->getChannelFromURL('http://www.youtube.com/user/Google');

        $this->assertEquals('UCK8sQmJBp8GCxrOtXWBpyEA', $channel->id);
        $this->assertEquals('Google', $channel->snippet->title);
    }

    public function testGetChannelFromURLWithChannel()
    {
        $channel = $this->youtube->getChannelFromURL('https://www.youtube.com/channel/UCyNF0DijeiZ8jhc_xlVa4Vg');

        $this->assertEquals('UCyNF0DijeiZ8jhc_xlVa4Vg', $channel->id);
        $this->assertEquals('PHPoC', $channel->snippet->title);
    }

    public function testGetChannelFromURLWithNoYoutubeUrlException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $channel = $this->youtube->getChannelFromURL('https://google.com');
    }

    public function testGetChannelFromURLWithInvalidYoutubeUrlException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $channel = $this->youtube->getChannelFromURL('https://www.youtube.com/invalid/UCyNF0DijeiZ8jhc_xlVa4Vg');
    }


    public function testDecodeListWithException()
    {
        $this->expectException(\Exception::class);
        $GOOGLE_ZEITGEIST_PLAYLIST = 'PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs';
        $params = array(
            'playlistId' => $GOOGLE_ZEITGEIST_PLAYLIST
        );
        $response = $this->youtube->getPlaylistItemsByPlaylistIdAdvanced($params, true);
    }

    public function testDecodeListWithFalse()
    {
        $limit = rand(3, 10);
        $params = array(
            'q' => 'Android',
            'type' => 'video',
            'channelId' => 'UCsxfgk64cpT61c8KZuaqUMA',
            'part' => 'id, snippet',
            'order' => 'title',
            'maxResults' => $limit
        );
        $response = $this->youtube->searchAdvanced($params);
        $this->assertFalse($response);
    }

    public function testApi_getWithException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $limit = rand(3, 10);
        $params = array(
            'q' => 'Android',
            'type' => 'video',
            'channelId' => 'UCsxfgk64cpT61c8KZuaqUMA',
            'order' => 'title',
            'maxResults' => $limit
        );
        $response = $this->youtube->api_get('http://404.php.net/', $params);
    }

    public function test_parse_url_queryWithEmptyArray()
    {
        $url = 'http://404.php.net';
        $youtube = $this->youtube;
        $response = $youtube::_parse_url_query($url);
        $this->assertCount(0, $response);
    }

    /**
     * Test skipped for now, since the API returns Error 500
     */
    public function testNotFoundAPICall()
    {
        $vID = 'Utn7NBtbHL4'; //an deleted video
        $response = $this->youtube->getVideoInfo($vID);
        $this->assertFalse($response);
    }
}