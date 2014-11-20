<?php
namespace SRIO\Discovered;

use SRIO\Discovered\Exception\ClientException;

class Client
{
    /**
     * Default protocol used if there's no protocol in the given address.
     *
     * @var string
     */
    const DEFAULT_PROTOCOL = 'http';

    /**
     * @var JsonRPCClient
     */
    protected $rpcClient;

    /**
     * Constructor.
     *
     * @param string $address
     * @throws \RuntimeException
     */
    public function __construct($address = null)
    {
        if (!$address) {
            $address = getenv('DISCOVERD').'/_goRPC_';

            if (!$address) {
                throw new \RuntimeException('Unable to find Discoverd address');
            }
        }

        $components = parse_url($this->normalizeAddress($address));
        $this->rpcClient = new JsonRPCClient($components['host'], $components['port'], $components['path'], array(
            'dial_headers' => array(
                'Accept' => 'application/vnd.flynn.rpc-hijack+json'
            )
        ));
    }

    /**
     * Subscribe to a service updates.
     *
     * @param $name
     * @return array
     */
    public function subscribe($name)
    {
        return $this->call('Agent.Subscribe', array(
            'Name' => $name
        ));
    }

    /**
     * Call Discoverd method.
     *
     * @param $method
     * @param array $params
     * @return mixed
     * @throws Exception\ClientException
     */
    protected function call($method, array $params)
    {
        $result = $this->rpcClient->call($method, $params);
        if (!array_key_exists('error', $result)) {
            throw new ClientException(sprintf(
                'Response seems to be malformated as the "error" key do not exists: %s',
                json_encode($result)
            ));
        } else if ($result['error'] !== null) {
            throw new ClientException(sprintf(
                'Server answered with an error: %s',
                $result['error']
            ));
        } else if (!array_key_exists('result', $result)) {
            throw new ClientException(sprintf(
                'Result is not found in server response (%s)',
                json_encode($result)
            ));
        }

        return $result['result'];
    }

    /**
     * Normalize the client address.
     *
     * @param $address
     * @return string
     */
    protected function normalizeAddress($address)
    {
        if (strpos($address, '://') === -1) {
            $address = self::DEFAULT_PROTOCOL.'://'.$address;
        }

        return $address;
    }
} 