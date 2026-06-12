<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *   @OA\Info(title="Test", version="1.0"),
 *   @OA\Server(url="http://localhost")
 * )
 *
 * @OA\Get(
 *   path="/test",
 *   summary="Test",
 *   @OA\Response(response=200, description="ok")
 * )
 */
class TestController {}
