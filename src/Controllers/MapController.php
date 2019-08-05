<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Respect\Validation\Validator as v;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Cocur\Slugify\Slugify;

/**
 * Description of UserController
 *
 * @author asok1
 */
class MapController extends AbstractController
{

    public function put(Request $request, Response $response, $args) {
        $validation = $this->validator->validate($request, [
            'name' => v::notEmpty()->stringType()->length(3, 25, true)
        ]);
        if ($validation->failed()) {
            return $response->withJson([
                        'status' => 'error',
                        'messages' => $validation->errors()
                            ], \Slim\Http\StatusCode::HTTP_BAD_REQUEST);
        }
        $name = $request->getParam('name');
        $slugify = new Slugify();
        $slug = $slugify->slugify($name);

        $map = \App\Models\Map::firstOrCreate(
                        [
                            'slug' => $slug . '-' . time(),
                        ],
                        [
                            'name' => $request->getParam('name'),
                            'center' => new Point(47.44295, 12.67116),
                            'min_zoom' => 3,
                            'max_zoom' => 16,
                            'zoom' => 5,
                            'label_zoom' => 5,
                        ]
        );

        return $response->withJson([
                    'slug' => $map->slug,
        ]);
    }

    public function list(Request $request, Response $response, $args) {
        $validation = $this->validator->validate($request, [
            'limit' => v::oneOf(
                    v::intVal()->min(1, true), v::nullType()
            ),
            'page' => v::oneOf(
                    v::intVal()->min(1, true), v::nullType()
            ),
        ]);
        if ($validation->failed()) {
            return $response->withJson([
                        'status' => 'error',
                        'messages' => $validation->errors()
                            ], \Slim\Http\StatusCode::HTTP_BAD_REQUEST);
        }
        $limit = (int) $request->getParam('limit', 10);
        $page = (int) $request->getParam('page', 1);

        $maps_query = \App\Models\Map::query();
        $total_maps = $maps_query->count();
        $maps = $maps_query
                ->forPage($page, $limit)
                ->without('features')
                ->withCount('features')
                ->get(['name', 'slug', 'updated_at', 'created_at']);

        return $response->withJson([
                    'maps' => $maps,
                    'total' => $total_maps
        ]);
    }

    public function get(Request $request, Response $response, $args) {
        $slug = $args['slug'];

        $map = \App\Models\Map::whereSlug($slug)->first();
        if (!$map) {
            return $response->withJson([
                        'status' => 'error',
                        'messages' => ['Record not found']
                            ], \Slim\Http\StatusCode::HTTP_NOT_FOUND);
        }

        return $response->withJson([
                    'map' => $map
        ]);
    }

    public function post(Request $request, Response $response, $args) {
        $slug = $args['slug'];
        $map = \App\Models\Map::whereSlug($slug)->first();
        if (!$map) {
            return $response->withJson([
                        'status' => 'error',
                        'messages' => ['Record not found']
                            ], \Slim\Http\StatusCode::HTTP_NOT_FOUND);
        }
        $validation = $this->validator->validate($request, [
            'min_zoom' => v::notEmpty()->intVal()->min(3, true)->max(18, true),
            'max_zoom' => v::notEmpty()->intVal()->min(3, true)->max(18, true),
            'zoom' => v::notEmpty()->intVal()->min(3, true)->max(18, true),
            'label_zoom' => v::notEmpty()->intVal()->min(3, true)->max(18, true),
            'center' => v::notEmpty()->arrayType(),
            'features' => v::arrayType()
        ]);
        if ($validation->failed()) {
            return $response->withJson([
                        'status' => 'error',
                        'messages' => $validation->errors()
                            ], \Slim\Http\StatusCode::HTTP_BAD_REQUEST);
        }
        if ($map->locked) {
            return $response->withJson([
                        'map' => $map
            ]);
        }

        $center = $request->getParam('center');
        $min_zoom = $request->getParam('min_zoom');
        $max_zoom = $request->getParam('max_zoom');
        $zoom = $request->getParam('zoom');
        $label_zoom = $request->getParam('label_zoom');
        $features = $request->getParam('features');

        try {
            $centerPoint = \Grimzy\LaravelMysqlSpatial\Types\Geometry::fromJson(json_encode($center));
        } catch (\GeoJson\Exception\UnserializationException $ex) {
            return $response->withJson([
                        'status' => 'error',
                        'messages' => [$ex->getMessage()]
                            ], \Slim\Http\StatusCode::HTTP_BAD_REQUEST);
        }
        $map->center = $centerPoint;
        $map->min_zoom = $min_zoom;
        $map->max_zoom = $max_zoom;
        $map->zoom = $zoom;
        $map->label_zoom = $label_zoom;
        $map->save();


        $saved_features = collect();
        try {
            foreach ($features as $feature) {
                $feature_obj = \Grimzy\LaravelMysqlSpatial\Types\Geometry::fromJson(json_encode($feature['feature']));
                $saved_features->push($map->features()->updateOrCreate(
                                ['id' => $feature['id']],
                                ['feature' => $feature_obj, 'name' => $feature['name']]
                ));
            }
        } catch (\GeoJson\Exception\UnserializationException $ex) {
            return $response->withJson([
                        'status' => 'error',
                        'messages' => [$ex->getMessage()]
                            ], \Slim\Http\StatusCode::HTTP_BAD_REQUEST);
        }

        $map->features()->whereNotIn('id', $saved_features->pluck('id'))->delete();

        return $response->withJson([
                    'map' => $map
        ]);
    }

    public function wikimapia_search(Request $request, Response $response, $args) {
        $validation = $this->validator->validate($request, [
            'query' => v::notEmpty()->stringType(),
            'lat' => v::oneOf(
                    v::floatVal()->min(-90, true)->max(90, true), v::nullType()
            ),
            'lon' => v::oneOf(
                    v::floatVal()->min(-180, true)->max(180, true), v::nullType()
            ),
            'limit' => v::oneOf(
                    v::intVal()->min(1, true)->max(100, true), v::nullType()
            ),
            'language' => v::oneOf(
                    v::stringType()->in(['ru', 'uk', 'en', 'pl']), v::nullType()
            )
        ]);
        if ($validation->failed()) {
            return $response->withJson([
                        'status' => 'error',
                        'messages' => $validation->errors()
                            ], \Slim\Http\StatusCode::HTTP_BAD_REQUEST);
        }

        $query = $request->getParam('query');
        $lat = $request->getParam('lat', 0);
        $lon = $request->getParam('lon', 0);
        $limit = $request->getParam('limit', 20);
        $language = $request->getParam('language', 'en');
        try {
            $httpClient = $this->container->get('httpClient');
            $params = [
                'function' => 'search',
                'key' => getenv('WIKIMAPIA.KEY'),
                'format' => 'json',
                'count' => $limit,
                'lat' => $lat,
                'lon' => $lon,
                'language' => $language,
                'q' => $query
            ];

            $apiResponse = $httpClient->get('http://api.wikimapia.org?' . http_build_query($params), [
                'timeout' => 5
            ]);
        } catch (TransferException $e) {
            return $response->withJson([
                        'status' => 'error',
                        'messages' => [$e->getMessage()]
                            ], \Slim\Http\StatusCode::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($apiResponse->getStatusCode() == 200) {
            $res = json_decode($apiResponse->getBody(), true);
            if (!empty($res['debug'])) {
                return $response->withJson([
                            'status' => 'error',
                            'messages' => [$res['debug']]
                                ], \Slim\Http\StatusCode::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            return $response->withJson([
                        'status' => 'error',
                        'messages' => ['Wikimapia error ' . $apiResponse->getStatusCode()]
                            ], \Slim\Http\StatusCode::HTTP_INTERNAL_SERVER_ERROR);
        }


        return $response->withJson([
                    'areas' => $res['folder']
        ]);
    }

}
