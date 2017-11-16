<?php

namespace DSL\Drivers\Catapult;

use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CatapultDriver extends HttpDriver
{
    const DRIVER_NAME = 'Catapult';

    protected $incomingKeys =
        [
            'eventType',
            'direction',
            'messageId',
            'messageUri',
            'from',
            'to',
            'text',
            'applicationId',
            'time',
            'state',
        ];

    /**
     * The chat messages.
     */
    protected $messages;

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = $request->request->all();
        $this->event   = Collection::make($this->payload);
        $this->config  = Collection::make($this->config->get('catapult', []));
    }

    /**
     * @param IncomingMessage $matchingMessage
     * @return \BotMan\BotMan\Users\User
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        return new User($matchingMessage->getSender());
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $payloadKeys    = array_keys($this->payload);
        $expectedValues = array_values($this->incomingKeys);

        $diff = array_diff($expectedValues, $payloadKeys);

        if (count($diff) !== 0) {
            return false;
        }

        $messageUri = $this->payload['messageUri'];

        if (strpos($messageUri, 'api.catapult.inetwork.com') === false) {
            return false;
        }

        return true;
    }

    /**
     * @param  IncomingMessage $message
     * @return \BotMan\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        if (empty($this->messages)) {
            $this->messages = [
                new IncomingMessage($this->event->get('text'),
                    $this->event->get('from'),
                    $this->event->get('to'),
                    $this->payload),
            ];
        }

        return $this->messages;
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return false;
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param IncomingMessage                 $matchingMessage
     * @param array                           $additionalParameters
     * @return Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $recipient = $matchingMessage->getRecipient();
        if ($recipient === '' || is_null($recipient)) {
            $recipient = $this->config->get('sender');
        }

        $parameters = array_merge(
            [
                'user_id'    => $this->config->get('user_id'),
                'app_token'  => $this->config->get('app_token'),
                'app_secret' => $this->config->get('app_secret'),
                'to'         => $matchingMessage->getSender(),
                'from'       => $recipient,
            ],
            $additionalParameters);

        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = $message->getText();
        } elseif ($message instanceof OutgoingMessage) {
            $parameters['text'] = $message->getText();
        } else {
            $parameters['text'] = $message;
        }

        return $parameters;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        throw new \BadMethodCallException(sprintf('%s is not yet implemented.', __METHOD__));
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return !empty($this->config->get('user_id')) &&
            !empty($this->config->get('app_token')) &&
            !empty($this->config->get('app_secret'));
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string          $endpoint
     * @param array           $parameters
     * @param IncomingMessage $matchingMessage
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        $parameters = array_replace_recursive(
            [
                'user_id'    => $this->config->get('user_id'),
                'api_token'  => $this->config->get('app_token'),
                'api_secret' => $this->config->get('app_secret'),
            ],
            $parameters);

        throw new \BadMethodCallException(sprintf('%s is not yet implemented.'), __METHOD__);
    }
}
