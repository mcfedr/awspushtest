<?php

namespace ekreative\PushBundle\Controller;

use ekreative\PushBundle\Exception\PlatformNotConfiguredException;
use ekreative\PushBundle\Message\Message;
use ekreative\PushBundle\Service\Devices;
use ekreative\PushBundle\Service\Messages;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class APIController extends Controller
{
    /**
     * @Route("/devices")
     * @Method({"POST"})
     */
    public function registerDeviceAction(Request $request) {
        $data = $this->handleJSONRequest($request);
        if($data instanceof Response) {
            return $data;
        }

        if(!isset($data['deviceID']) || !isset($data['platform'])) {
            return new Response('Missing parameters', 400);
        }

        try {
            if($this->getPushDevices()->registerDevice($data['deviceID'], $data['platform'])) {
                return new Response('Device registered', 200);
            }
        }
        catch(PlatformNotConfiguredException $e) {
            return new Response('Unknown platform', 400);
        }

        return new Response('Unknown error', 500);
    }

    /**
     * @Route("/broadcast")
     * @Method({"POST"})
     */
    public function broadcastAction(Request $request) {
        $data = $this->handleJSONRequest($request);
        if($data instanceof Response) {
            return $data;
        }

        if(!isset($data['message'])) {
            return new Response('You must send a message', 400);
        }

        try {
            $m = new Message($data['message']);
            $m->setCustom([
                'message' => $data['message']
            ]);
            $this->getPushMessages()->broadcast($m, isset($data['platform']) ? $data['platform'] : null);
            return new Response('Message sent', 200);
        }
        catch(PlatformNotConfiguredException $e) {
            return new Response('Unknown platform', 400);
        }

        return new Response('Unknown error', 500);
    }

    private function handleJSONRequest(Request $request) {
        $data = json_decode($request->getContent(), true);
        if($data === null) {
            return new Response("Invalid Request JSON", 400);
        }
        return $data;
    }

    /**
     * @return Devices
     */
    private function getPushDevices() {
        return $this->get('pushDevices');
    }

    /**
     * @return Messages
     */
    private function getPushMessages() {
        return $this->get('pushMessages');
    }
}
