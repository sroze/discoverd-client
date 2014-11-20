<?php
namespace SRIO\Discovered;

use SRIO\Discovered\Exception\JsonRPCException;

class JsonRPCClient
{
    private $host;
    private $port;
    private $path;
    private $options;
    private $connection = null;
    private $reqId = null;

    /**
     * Constructor.
     *
     * @param $host
     * @param $port
     * @param $path
     * @param array $options
     */
    function __construct($host, $port, $path, array $options = array())
    {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->options = $options;
    }

    /**
     * Initialize connection to JSON-RPC server.
     *
     * @throws Exception\JsonRPCException
     */
    function dial()
    {
        $conn = @fsockopen($this->host, $this->port, $errorNumber, $errorString, 5);
        if (!$conn) {
            throw new JsonRPCException(sprintf(
                'An error appeared while connecting to RPC server: %s (%d)',
                $errorNumber,
                $errorString
            ), $errorNumber);
        }

        $request = 'CONNECT '.$this->path.' HTTP/1.0'."\n";
        if (array_key_exists('dial_headers', $this->options)) {
            foreach ($this->options['dial_headers'] as $header => $value) {
                $request .= $header.': '.$value."\n";
            }
        }

        @fwrite($conn, $request."\n");
        stream_set_timeout($conn, 0, 3000);
        $line = @fgets($conn);

        $success = 'HTTP/1.0 200 Connected';
        if (substr($line, 0, strlen($success)) != $success) {
            @fclose($conn);

            throw new JsonRPCException(sprintf('Unexpected HTTP response while connecting: %s', $line));
        }

        $this->connection = $conn;
    }

    /**
     * Call a method of JSON-RPC client.
     *
     * @param $method
     * @param $params
     * @return array
     * @throws Exception\JsonRPCException
     */
    function call($method, $params)
    {
        if ($this->connection === null) {
            $this->dial();
        }

        $request = array(
            'Method' => $method,
            'Params' => array($params),
            'Id' => $this->reqId,
        );

        $request = json_encode($request);
        if (fwrite($this->connection, $request."\n") === false) {
            throw new JsonRPCException(sprintf(
                'An error appeared while sending request to client'
            ));
        }

        // Read while line is not empty
        $line = null;
        while ($line == null || $line == "\n") {
            $line = @fgets($this->connection);
        }

        $this->reqId += 1;

        // Read the received line
        $decoded = json_decode($line, true);
        if ($decoded === null) {
            throw new JsonRPCException(sprintf(
                'Unable to decode line received from client (%s)',
                $line
            ));
        }

        return $decoded;
    }
}
