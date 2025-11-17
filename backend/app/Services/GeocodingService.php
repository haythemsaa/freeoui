<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    protected string $provider;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->provider = config('services.geocoding.provider', 'nominatim');
        $this->apiKey = config('services.geocoding.api_key');
    }

    /**
     * Geocode an address to coordinates
     */
    public function geocode(string $address): ?array
    {
        $cacheKey = "geocode:" . md5($address);

        return Cache::remember($cacheKey, 86400, function () use ($address) {
            try {
                switch ($this->provider) {
                    case 'nominatim':
                        return $this->geocodeNominatim($address);

                    case 'google':
                        return $this->geocodeGoogle($address);

                    default:
                        return null;
                }
            } catch (\Exception $e) {
                Log::error('Geocoding failed', [
                    'address' => $address,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Reverse geocode coordinates to address
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        $cacheKey = "reverse_geocode:{$latitude},{$longitude}";

        return Cache::remember($cacheKey, 86400, function () use ($latitude, $longitude) {
            try {
                switch ($this->provider) {
                    case 'nominatim':
                        return $this->reverseGeocodeNominatim($latitude, $longitude);

                    case 'google':
                        return $this->reverseGeocodeGoogle($latitude, $longitude);

                    default:
                        return null;
                }
            } catch (\Exception $e) {
                Log::error('Reverse geocoding failed', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Geocode using Nominatim (OpenStreetMap)
     */
    protected function geocodeNominatim(string $address): ?array
    {
        $response = Http::get('https://nominatim.openstreetmap.org/search', [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
            'countrycodes' => 'tn', // Tunisia
        ]);

        if ($response->successful() && !empty($response->json())) {
            $result = $response->json()[0];

            return [
                'latitude' => (float) $result['lat'],
                'longitude' => (float) $result['lon'],
                'formatted_address' => $result['display_name'],
                'provider' => 'nominatim',
            ];
        }

        return null;
    }

    /**
     * Reverse geocode using Nominatim
     */
    protected function reverseGeocodeNominatim(float $latitude, float $longitude): ?array
    {
        $response = Http::get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $latitude,
            'lon' => $longitude,
            'format' => 'json',
        ]);

        if ($response->successful()) {
            $result = $response->json();

            return [
                'formatted_address' => $result['display_name'],
                'city' => $result['address']['city'] ?? $result['address']['town'] ?? null,
                'state' => $result['address']['state'] ?? null,
                'country' => $result['address']['country'] ?? null,
                'postal_code' => $result['address']['postcode'] ?? null,
                'provider' => 'nominatim',
            ];
        }

        return null;
    }

    /**
     * Geocode using Google Maps
     */
    protected function geocodeGoogle(string $address): ?array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Google Maps API key not configured');
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => $this->apiKey,
            'region' => 'tn',
        ]);

        if ($response->successful() && $response->json('status') === 'OK') {
            $result = $response->json('results')[0];
            $location = $result['geometry']['location'];

            return [
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'formatted_address' => $result['formatted_address'],
                'provider' => 'google',
            ];
        }

        return null;
    }

    /**
     * Reverse geocode using Google Maps
     */
    protected function reverseGeocodeGoogle(float $latitude, float $longitude): ?array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Google Maps API key not configured');
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng' => "{$latitude},{$longitude}",
            'key' => $this->apiKey,
        ]);

        if ($response->successful() && $response->json('status') === 'OK') {
            $result = $response->json('results')[0];

            $addressComponents = collect($result['address_components']);

            return [
                'formatted_address' => $result['formatted_address'],
                'city' => $addressComponents->firstWhere('types', ['locality'])['long_name'] ?? null,
                'state' => $addressComponents->firstWhere('types', ['administrative_area_level_1'])['long_name'] ?? null,
                'country' => $addressComponents->firstWhere('types', ['country'])['long_name'] ?? null,
                'postal_code' => $addressComponents->firstWhere('types', ['postal_code'])['long_name'] ?? null,
                'provider' => 'google',
            ];
        }

        return null;
    }

    /**
     * Get city name from coordinates
     */
    public function getCityName(float $latitude, float $longitude): ?string
    {
        $result = $this->reverseGeocode($latitude, $longitude);

        return $result['city'] ?? null;
    }

    /**
     * Validate Tunisia coordinates
     */
    public function isInTunisia(float $latitude, float $longitude): bool
    {
        // Tunisia bounding box (approximate)
        return $latitude >= 30.0 && $latitude <= 38.0
            && $longitude >= 7.0 && $longitude <= 12.0;
    }
}
