<?php
namespace App\Controllers;
use OpenApi\Attributes as OA;
/** 
 * @OA\Info(
 *     version="3.0",
 *     title="Example API",
 *     description="Example info",
 *     @OA\Contact(name="Swagger API Team")
 * )
 * @OA\Server(
 *     url="https://localhost",
 *     description="API server"
 * )
 * @OA\PathItem(
 *     path="../public",
 *     summary="https://wevitt.com", 
 * ),
 * @OA\SecurityScheme(
 *      type="http",
 *      description="Use /login to get a token ",
 *      name="Authorization",
 *      in="header",      
 *      scheme="bearer",
 *      bearerFormat="JWT",
 *      securityScheme="bearerAuth",
 * ),
 */
class ApiDocument 
{
    //swagger config
}