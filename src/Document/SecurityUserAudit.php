<?php
/**
 * SecurityUserAudit
 */
namespace Graviton\CommonBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Serializable;
use MongoDB\BSON\UTCDateTime;

/**
 * Class SecurityUserAudit
 * @package GatewaySecurityBundle\Document
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://swisscom.ch
 */
#[MongoDB\Document(collection: "SecurityUserAudit")]
class SecurityUserAudit implements Serializable, \JsonSerializable
{
    /**
     * @var ObjectId $id
     */
    #[MongoDB\Id(strategy: "AUTO")]
    public $id;

    #[MongoDB\Field(type: "string")]
    public $app;

    #[MongoDB\Field(type: "string")]
    #[MongoDB\Index]
    public $username;

    /**
     * @var \DateTime $createdAt
     */
    #[MongoDB\Field(type: "date")]
    #[MongoDB\Index]
    public $createdAt;

    /**
     * @var string $method
     */
    #[MongoDB\Field(type: "string")]
    #[MongoDB\Index]
    public $method;

    /**
     * @var string $requestUri
     */
    #[MongoDB\Field(type: "string")]
    public $requestUri;

    /**
     * @var string $responseCode
     */
    #[MongoDB\Field(type: "integer")]
    #[MongoDB\Index]
    public $responseCode;

	/**
	 * @var string $requestBody
	 */
    #[MongoDB\Field(type: "string")]
    public $requestBody;

    /**
     * @var string $requestBody
     */
    #[MongoDB\Field(type: "string")]
    public $responseBody;

    /**
     * @var float $requestTimeMs
     */
    #[MongoDB\Field(type: "float")]
    public $requestTimeMs;

    /**
     * @var float $requestTimeGatewayMs
     */
    #[MongoDB\Field(type: "float")]
    public $requestTimeGatewayMs;

    /**
     * @var float $requestTimeClientMs
     */
    #[MongoDB\Field(type: "float")]
    public $requestTimeClientMs;

    /**
     * @return ObjectId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param ObjectId $id ObjectId
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getApp(): ?string {
        return $this->app;
    }

    /**
     * @param string $app
     */
    public function setApp(?string $app): void {
        $this->app = $app;
    }

    /**
     * get Username
     *
     * @return string Username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * set Username
     *
     * @param string $username username
     *
     * @return void
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * get CreatedAt
     *
     * @return \DateTime CreatedAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * set CreatedAt
     *
     * @param \DateTime $createdAt createdAt
     *
     * @return void
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * get Method
     *
     * @return string Method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * set Method
     *
     * @param string $method method
     *
     * @return void
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * get RequestUri
     *
     * @return string RequestUri
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * set RequestUri
     *
     * @param string $requestUri requestUri
     *
     * @return void
     */
    public function setRequestUri($requestUri)
    {
        $this->requestUri = $requestUri;
    }

    /**
     * get ResponseCode
     *
     * @return string ResponseCode
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * set ResponseCode
     *
     * @param string $responseCode responseCode
     *
     * @return void
     */
    public function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;
    }

	/**
	 * get RequestBody
	 *
	 * @return string RequestBody
	 */
	public function getRequestBody() {
		return $this->requestBody;
	}

	/**
	 * set RequestBody
	 *
	 * @param string $requestBody requestBody
	 *
	 * @return void
	 */
	public function setRequestBody($requestBody) {
		$this->requestBody = $requestBody;
	}

    /**
     * get ResponseBody
     *
     * @return string ResponseBody
     */
    public function getResponseBody() {
        return $this->responseBody;
    }

    /**
     * set ResponseBody
     *
     * @param string $responseBody responseBody
     *
     * @return void
     */
    public function setResponseBody($responseBody) {
        $this->responseBody = $responseBody;
    }

    /**
     * @return float
     */
    public function getRequestTimeMs(): ?float
    {
        return $this->requestTimeMs;
    }

    /**
     * @param float $requestTimeMs
     */
    public function setRequestTimeMs(float $requestTimeMs): void
    {
        $this->requestTimeMs = $requestTimeMs;
    }

    /**
     * @return float
     */
    public function getRequestTimeGatewayMs(): ?float
    {
        return $this->requestTimeGatewayMs;
    }

    /**
     * @param float $requestTimeGatewayMs
     */
    public function setRequestTimeGatewayMs(float $requestTimeGatewayMs): void
    {
        $this->requestTimeGatewayMs = $requestTimeGatewayMs;
    }

    /**
     * @return float
     */
    public function getRequestTimeClientMs(): ?float
    {
        return $this->requestTimeClientMs;
    }

    /**
     * @param float $requestTimeClientMs
     */
    public function setRequestTimeClientMs(float $requestTimeClientMs): void
    {
        $this->requestTimeClientMs = $requestTimeClientMs;
    }

    public function bsonSerialize() : array {
        $arr = $this->jsonSerialize();
        unset($arr['id']);

        $arr['_id'] = $this->getId();
        $arr['createdAt'] = new UTCDateTime($this->getCreatedAt());

        return $arr;
    }

    public function jsonSerialize() : mixed {
        return [
            'id' => (string) $this->getId(),
            'app' => $this->getApp(),
            'username' => $this->getUsername(),
            'createdAt' => $this->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'method' => $this->getMethod(),
            'requestUri' => $this->getRequestUri(),
            'responseCode' => $this->getResponseCode(),
            'requestBody' => $this->getRequestBody(),
            'responseBody' => $this->getResponseBody(),
            'requestTimeMs' => $this->getRequestTimeMs(),
            'requestTimeGatewayMs' => $this->getRequestTimeGatewayMs(),
            'requestTimeClientMs' => $this->getRequestTimeClientMs(),
        ];
    }
}
