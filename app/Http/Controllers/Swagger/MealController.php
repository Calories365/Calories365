<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Meals",
 *     description="CRUD for FoodConsumption of the current user"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum: send token via Authorization: Bearer <token>"
 * )
 */
class MealController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/meals",
     *     operationId="meals.store",
     *     tags={"Meals"},
     *     summary="Create a meal entry",
     *     description="Creates FoodConsumption for the current user.",
     *     security={{"sanctum":{}}},
     *     requestBody=@OA\RequestBody(
     *         request="StoreMealRequest",
     *         required=true,
     *         description="Required payload to create FoodConsumption",
     *         content={
     *
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *
     *                 @OA\Schema(
     *                     type="object",
     *                     required={"food_id","quantity","consumed_at","part_of_day"},
     *
     *                     @OA\Property(
     *                         property="food_id",
     *                         type="integer",
     *                         example=123,
     *                         description="Existing product ID (must exist in products.id)"
     *                     ),
     *                     @OA\Property(
     *                         property="quantity",
     *                         type="number",
     *                         format="float",
     *                         minimum=0,
     *                         example=150
     *                     ),
     *                     @OA\Property(
     *                         property="consumed_at",
     *                         type="string",
     *                         format="date",
     *                         pattern="^[0-9]{4}-[0-9]{2}-[0-9]{2}$",
     *                         example="2025-08-20",
     *                         description="Date in YYYY-MM-DD format"
     *                     ),
     *                     @OA\Property(
     *                         property="part_of_day",
     *                         type="string",
     *                         enum={"morning","dinner","supper"},
     *                         example="morning"
     *                     ),
     *                     example={"food_id":123,"quantity":150,"consumed_at":"2025-08-20","part_of_day":"morning"}
     *                 )
     *             )
     *         }
     *     ),
     *
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store() {}

    /**
     * @OA\Get(
     *     path="/api/meals/{date}",
     *     operationId="meals.index",
     *     tags={"Meals"},
     *     summary="List meals by date",
     *     description="Returns user's meals for the given date, optionally filtered by part of day.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="date",
     *         in="path",
     *         required=true,
     *         description="Date in format YYYY-MM-DD",
     *
     *         @OA\Schema(type="string", format="date", example="2025-08-20")
     *     ),
     *
     *     @OA\Parameter(
     *         name="partOfDay",
     *         in="query",
     *         required=false,
     *         description="Optional filter by part of day",
     *
     *         @OA\Schema(type="string", enum={"morning","dinner","supper"}, example="dinner")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(
     *                 type="object",
     *                 required={"id","user_id","quantity","part_of_day","food_id","consumed_at","calories","proteins","carbohydrates","fats","fibers","name"},
     *
     *                 @OA\Property(property="id", type="integer", example=101),
     *                 @OA\Property(property="user_id", type="integer", example=42),
     *                 @OA\Property(property="quantity", type="number", format="float", example=150),
     *                 @OA\Property(property="part_of_day", type="string", enum={"morning","dinner","supper"}, example="dinner"),
     *                 @OA\Property(property="food_id", type="integer", example=123),
     *                 @OA\Property(property="consumed_at", type="string", format="date", example="2025-08-20"),
     *                 @OA\Property(property="calories", type="number", format="float", example=215.5),
     *                 @OA\Property(property="proteins", type="number", format="float", example=12.3),
     *                 @OA\Property(property="carbohydrates", type="number", format="float", example=28.7),
     *                 @OA\Property(property="fats", type="number", format="float", example=7.4),
     *                 @OA\Property(property="fibers", type="number", format="float", example=3.1),
     *                 @OA\Property(property="name", type="string", example="Овсянка")
     *             ),
     *             example={{
     *                 "id":101,"user_id":42,"quantity":150,"part_of_day":"dinner","food_id":123,"consumed_at":"2025-08-20",
     *                 "calories":215.5,"proteins":12.3,"carbohydrates":28.7,"fats":7.4,"fibers":3.1,"name":"Овсянка"
     *             },{
     *                 "id":102,"user_id":42,"quantity":80,"part_of_day":"morning","food_id":321,"consumed_at":"2025-08-20",
     *                 "calories":120.0,"proteins":6.0,"carbohydrates":14.0,"fats":3.0,"fibers":2.0,"name":"Йогурт"
     *             }}
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function index() {}

    /**
     * @OA\Put(
     *     path="/api/meals/{meal}",
     *     operationId="meals.update",
     *     tags={"Meals"},
     *     summary="Update meal quantity",
     *     description="Updates the 'quantity' field for a FoodConsumption record.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="meal",
     *         in="path",
     *         required=true,
     *         description="FoodConsumption ID",
     *
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     requestBody=@OA\RequestBody(
     *         request="UpdateMealQuantityRequest",
     *         required=true,
     *         description="Payload defined by QuantityValidationRequest",
     *
     *         @OA\JsonContent(
     *             type="object",
     *             required={"quantity"},
     *
     *             @OA\Property(
     *                 property="quantity",
     *                 type="number",
     *                 format="float",
     *                 minimum=0,
     *                 example=150
     *             ),
     *             example={"quantity":150}
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="quantity",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The quantity must be at least 0.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function update() {}

    /**
     * @OA\Delete(
     *     path="/api/meals/{meal}",
     *     operationId="meals.destroy",
     *     tags={"Meals"},
     *     summary="Delete a meal",
     *     description="Deletes a FoodConsumption record.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="meal",
     *         in="path",
     *         required=true,
     *         description="FoodConsumption ID",
     *
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="Success")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function destroy() {}
}
