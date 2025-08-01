<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *     schema="UserRequest",
 *     required={"name", "email", "jenjang", "password", "jenis_lomba"},
 *     @OA\Property(property="name", type="string", maxLength=255, example="Siti Aminah"),
 *     @OA\Property(property="email", type="string", format="email", example="siti@example.com"),
 *     @OA\Property(property="jenjang", type="string", maxLength=255, example="SMP"),
 *     @OA\Property(property="password", type="string", format="password", minLength=8, example="rahasia123"),
 *     @OA\Property(property="jenis_lomba", type="string", maxLength=255, example="IPA")
 * )
 */
class UserRequestSchema {}