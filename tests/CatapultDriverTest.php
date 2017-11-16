<?php

namespace Tests;

use BotMan\BotMan\Http\Curl;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use DSL\Drivers\Catapult\CatapultDriver;
use Mockery as m;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;

class CatapultDriverTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('Catapult', $driver->getName());
    }

    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = Request::create('', 'POST', $responseData);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new CatapultDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_matches_the_request()
    {
        $badData = [
            'missing message URI' => [
                'eventType'     => 'sms',
                'direction'     => 'in',
                'messageId'     => '{messageId}',
                /*
                 *  Deliberately missing URI.
                 *
                 *  'messageUri'    => 'https://api.catapult.inetwork.com/v1/users/{userId}/messages/{messageId}',
                 */
                'from'          => '+13233326955',
                'to'            => '+13865245000',
                'text'          => 'Example',
                'applicationId' => '{appId}',
                'time'          => '2012-11-14T16:13:06.076Z',
                'state'         => 'received',
            ],
            'invalid message URI' => [
                'eventType'     => 'sms',
                'direction'     => 'in',
                'messageId'     => '{messageId}',
                /*
                 * Bad URI.
                 */
                'messageUri'    => 'https://bad.network.com/v1/users/{userId}/messages/{messageId}',
                'from'          => '+13233326955',
                'to'            => '+13865245000',
                'text'          => 'Example',
                'applicationId' => '{appId}',
                'time'          => '2012-11-14T16:13:06.076Z',
                'state'         => 'received',

            ],
        ];

        foreach ($badData as $message => $data) {
            $driver = $this->getDriver($data);

            $this->assertFalse($driver->matchesRequest(), "$message should not have matched!");
        }

        $goodData = [
            'missing message URI' => [
                'eventType'     => 'sms',
                'direction'     => 'in',
                'messageId'     => '{messageId}',
                'messageUri'    => 'https://api.catapult.inetwork.com/v1/users/{userId}/messages/{messageId}',
                'from'          => '+13233326955',
                'to'            => '+13865245000',
                'text'          => 'Example',
                'applicationId' => '{appId}',
                'time'          => '2012-11-14T16:13:06.076Z',
                'state'         => 'received',
            ],
        ];

        foreach ($goodData as $message => $data) {
            $driver = $this->getDriver($data);

            $this->assertTrue($driver->matchesRequest(), "$message should have matched!");
        }
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver([
            'eventType'     => 'sms',
            'direction'     => 'in',
            'messageId'     => '{messageId}',
            'messageUri'    => 'https://api.catapult.inetwork.com/v1/users/{userId}/messages/{messageId}',
            'from'          => '+13233326955',
            'to'            => '+13865245000',
            'text'          => 'Example',
            'applicationId' => '{appId}',
            'time'          => '2012-11-14T16:13:06.076Z',
            'state'         => 'received',
        ]);

        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_messages_by_reference()
    {
        $driver = $this->getDriver([
            'eventType'     => 'sms',
            'direction'     => 'in',
            'messageId'     => '{messageId}',
            'messageUri'    => 'https://api.catapult.inetwork.com/v1/users/{userId}/messages/{messageId}',
            'from'          => '+13233326955',
            'to'            => '+13865245000',
            'text'          => 'Example',
            'applicationId' => '{appId}',
            'time'          => '2012-11-14T16:13:06.076Z',
            'state'         => 'received',
        ]);

        $hash = spl_object_hash($driver->getMessages()[0]);

        $this->assertSame($hash, spl_object_hash($driver->getMessages()[0]));
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $driver = $this->getDriver([
            'eventType'     => 'sms',
            'direction'     => 'in',
            'messageId'     => '{messageId}',
            'messageUri'    => 'https://api.catapult.inetwork.com/v1/users/{userId}/messages/{messageId}',
            'from'          => '+13233326955',
            'to'            => '+13865245000',
            'text'          => 'Example',
            'applicationId' => '{appId}',
            'time'          => '2012-11-14T16:13:06.076Z',
            'state'         => 'received',
        ]);

        $this->assertSame('Example', $driver->getMessages()[0]->getText());
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getDriver([
            'eventType'     => 'sms',
            'direction'     => 'in',
            'messageId'     => '{messageId}',
            'messageUri'    => 'https://api.catapult.inetwork.com/v1/users/{userId}/messages/{messageId}',
            'from'          => '+13233326955',
            'to'            => '+13865245000',
            'text'          => 'Example',
            'applicationId' => '{appId}',
            'time'          => '2012-11-14T16:13:06.076Z',
            'state'         => 'received',
        ]);

        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getDriver([
            'eventType'     => 'sms',
            'direction'     => 'in',
            'messageId'     => '{messageId}',
            'messageUri'    => 'https://api.catapult.inetwork.com/v1/users/{userId}/messages/{messageId}',
            'from'          => '+13233326955',
            'to'            => '+13865245000',
            'text'          => 'Example',
            'applicationId' => '{appId}',
            'time'          => '2012-11-14T16:13:06.076Z',
            'state'         => 'received',
        ]);

        $this->assertSame('+13233326955', $driver->getMessages()[0]->getSender());
    }

    /** @test */
    public function it_returns_the_from()
    {
        $driver = $this->getDriver([
            'eventType'     => 'sms',
            'direction'     => 'in',
            'messageId'     => '{messageId}',
            'messageUri'    => 'https://api.catapult.inetwork.com/v1/users/{userId}/messages/{messageId}',
            'from'          => '+13233326955',
            'to'            => '+13865245000',
            'text'          => 'Example',
            'applicationId' => '{appId}',
            'time'          => '2012-11-14T16:13:06.076Z',
            'state'         => 'received',
        ]);

        $this->assertSame('+13865245000', $driver->getMessages()[0]->getRecipient());
    }

    /** @test */
    public function it_returns_the_user_object()
    {
        $driver = $this->getDriver(
            [
                'eventType'     => 'sms',
                'direction'     => 'in',
                'messageId'     => '{messageId}',
                'messageUri'    => 'https://api.catapult.inetwork.com/v1/users/{userId}/messages/{messageId}',
                'from'          => '+13233326955',
                'to'            => '+13865245000',
                'text'          => 'Example',
                'applicationId' => '{appId}',
                'time'          => '2012-11-14T16:13:06.076Z',
                'state'         => 'received',
            ]);

        $message = $driver->getMessages()[0];
        $user    = $driver->getUser($message);

        $this->assertSame($user->getId(), '+13233326955');
        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());
        $this->assertnull($user->getUserName());
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class . '[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new CatapultDriver($request, [
            'catapult' =>
                [
                    'user_id'    => 'user_id',
                    'app_token'  => 'token',
                    'app_secret' => 'secret',
                ],
        ], $htmlInterface);

        $this->assertTrue($driver->isConfigured());
    }

    /** @test */
    public function it_can_build_payload()
    {
        $request = m::mock(Request::class . '[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new CatapultDriver($request, [
            'catapult' =>
                [
                    'user_id'    => 'user_id',
                    'app_token'  => 'token',
                    'app_secret' => 'secret',
                ],
        ], $htmlInterface);

        $incomingMessage = new IncomingMessage('text', '123456', '987654');

        $message = 'string';
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame(
            [
                'user_id'    => 'user_id',
                'app_token'  => 'token',
                'app_secret' => 'secret',
                'to'         => '123456',
                'from'       => '987654',
                'text'       => 'string',
            ],
            $payload);

        $message = new OutgoingMessage('message object');
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame([
            'user_id'    => 'user_id',
            'app_token'  => 'token',
            'app_secret' => 'secret',
            'to'         => '123456',
            'from'       => '987654',
            'text'       => 'message object',
        ],
            $payload);

        $message = new Question('question object');
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame(
            [
                'user_id'    => 'user_id',
                'app_token'  => 'token',
                'app_secret' => 'secret',
                'to'         => '123456',
                'from'       => '987654',
                'text'       => 'question object',
            ],
            $payload);
    }

    /** @test */
    public function it_can_build_payload_with_additional_parameters()
    {
        $request = m::mock(Request::class . '[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new CatapultDriver($request, [
            'catapult' =>
                [
                    'user_id'    => 'user_id',
                    'app_token'  => 'token',
                    'app_secret' => 'secret',
                ],
        ], $htmlInterface);

        $incomingMessage = new IncomingMessage('text', '123456', '987654');

        $message = 'string';
        $payload = $driver->buildServicePayload($message,
            $incomingMessage,
            [
                'from' => 'custom',
            ]);

        $this->assertSame([
            'user_id'    => 'user_id',
            'app_token'  => 'token',
            'app_secret' => 'secret',
            'to'         => '123456',
            'from'       => 'custom',
            'text'       => 'string',
        ],
            $payload);
    }

    /** @test */
    public function it_uses_fallback_from_number()
    {
        $request = m::mock(Request::class . '[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new CatapultDriver($request, [
            'catapult' =>
                [
                    'user_id'    => 'user_id',
                    'app_token'  => 'token',
                    'app_secret' => 'secret',
                    'sender'     => '004912345',
                ],
        ], $htmlInterface);

        $incomingMessage = new IncomingMessage('text', '123456', '');

        $message = 'string';
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame([
            'user_id'    => 'user_id',
            'app_token'  => 'token',
            'app_secret' => 'secret',
            'to'         => '123456',
            'from'       => '004912345',
            'text'       => 'string',
        ],
            $payload);
    }

    /** @test */
    public function it_can_send_payload()
    {
        $request = m::mock(Request::class . '[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new CatapultDriver($request, [
            'nexmo' => [
                'app_key'    => 'key',
                'app_secret' => 'secret',
            ],
        ], $htmlInterface);

        $payload = [
            'api_key'    => 'key',
            'api_secret' => 'secret',
            'to'         => '123456',
            'from'       => '987654',
            'text'       => 'string',
        ];

        $htmlInterface->shouldReceive('post')
                      ->once()
                      ->with('https://rest.nexmo.com/sms/json?' . http_build_query($payload));

        $driver->sendPayload($payload);
    }

//    /** @test */
//    public function it_can_send_requests()
//    {
//        $this->markTestIncomplete(sprintf('Not yet implemented: ' . __METHOD__));
//        /*
//        $request = m::mock(Request::class . '[getContent]');
//        $request->shouldReceive('getContent')->andReturn('');
//        $htmlInterface = m::mock(Curl::class);
//
//        $driver = new CatapultDriver($request, [
//            'nexmo' => [
//                'app_key'    => 'key',
//                'app_secret' => 'secret',
//            ],
//        ], $htmlInterface);
//
//        $parameters = [
//            'to'   => '123456',
//            'from' => '987654',
//            'text' => 'string',
//        ];
//
//        $payload = [
//            'api_key'    => 'key',
//            'api_secret' => 'secret',
//            'to'         => '123456',
//            'from'       => '987654',
//            'text'       => 'string',
//        ];
//
//        $htmlInterface->shouldReceive('post')
//                      ->once()
//                      ->with('https://rest.nexmo.com/foo/json?' . http_build_query($payload));
//
//        $incomingMessage = new IncomingMessage('text', '123456', '987654');
//        $driver->sendRequest('foo/json', $payload, $incomingMessage);
//        */
//    }
//
//    /** @test */
//    public function it_can_get_conversation_answers()
//    {
//        $this->markTestIncomplete(sprintf('Not yet implemented: ' . __METHOD__));
//        /*
//        $request = m::mock(Request::class . '[getContent]');
//        $request->shouldReceive('getContent')->andReturn('');
//        $htmlInterface = m::mock(Curl::class);
//
//        $driver = new CatapultDriver($request, [
//            'nexmo' => [
//                'app_key'    => 'key',
//                'app_secret' => 'secret',
//            ],
//        ], $htmlInterface);
//
//        $incomingMessage = new IncomingMessage('text', '123456', '987654');
//        $answer          = $driver->getConversationAnswer($incomingMessage);
//
//        $this->assertSame('text', $answer->getText());
//        */
//    }
}
