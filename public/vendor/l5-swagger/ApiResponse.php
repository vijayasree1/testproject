<?php
namespace App\Http;

class ApiResponse


/**
 * @SWG\Schema(required={"id", "origin"})
 */

{
    /**
     * @SWG\Property(format="int32")
     * @var int
     */
    public $id;
    /**
     * @SWG\Property
     * @var string
     */
    public $origin;
    /**
     * @SWG\Property
     * @var string
     */
    public $message;
}