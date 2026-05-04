<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogSearchRequest;
use App\Http\Requests\PriceSearchRequest;
use App\Http\Requests\UasgSearchRequest;
use App\Services\ComprasGov\MaterialCatalogService;
use App\Services\ComprasGov\PriceResearchService;
use App\Services\ComprasGov\ServiceCatalogService;
use App\Services\ComprasGov\UasgService;
use Illuminate\Http\JsonResponse;

class ComprasGovLookupController extends Controller
{
    public function materialGroups(CatalogSearchRequest $request, MaterialCatalogService $service): JsonResponse
    {
        session_write_close();
        return response()->json($service->getGroups());
    }

    public function materialClasses(CatalogSearchRequest $request, MaterialCatalogService $service): JsonResponse
    {
        session_write_close();
        return response()->json($service->getClasses((int) $request->codigoGrupo));
    }

    public function materialPdms(CatalogSearchRequest $request, MaterialCatalogService $service): JsonResponse
    {
        session_write_close();
        return response()->json($service->getPdms((int) $request->codigoClasse));
    }

    public function materialItems(CatalogSearchRequest $request, MaterialCatalogService $service): JsonResponse
    {
        session_write_close();
        return response()->json($service->searchItems($this->normalize($request)));
    }

    public function materialUnits(CatalogSearchRequest $request, MaterialCatalogService $service): JsonResponse
    {
        return response()->json($service->supplyUnits($this->normalize($request)));
    }

    public function materialCharacteristics(CatalogSearchRequest $request, MaterialCatalogService $service): JsonResponse
    {
        return response()->json($service->characteristics($this->normalize($request)));
    }

    public function serviceSections(CatalogSearchRequest $request, ServiceCatalogService $service): JsonResponse
    {
        session_write_close();
        return response()->json($service->getSections());
    }

    public function serviceDivisions(CatalogSearchRequest $request, ServiceCatalogService $service): JsonResponse
    {
        session_write_close();
        return response()->json($service->getDivisions($request->codigoSecao));
    }

    public function serviceGroups(CatalogSearchRequest $request, ServiceCatalogService $service): JsonResponse
    {
        session_write_close();
        return response()->json($service->getGroups((int) $request->codigoDivisao));
    }

    public function serviceClasses(CatalogSearchRequest $request, ServiceCatalogService $service): JsonResponse
    {
        session_write_close();
        return response()->json($service->getClasses((int) $request->codigoGrupo));
    }

    public function serviceSubclasses(CatalogSearchRequest $request, ServiceCatalogService $service): JsonResponse
    {
        session_write_close();
        return response()->json($service->getSubclasses((int) $request->codigoClasse));
    }

    public function serviceItems(CatalogSearchRequest $request, ServiceCatalogService $service): JsonResponse
    {
        session_write_close();
        return response()->json($service->searchItems($this->normalize($request)));
    }

    public function serviceUnits(CatalogSearchRequest $request, ServiceCatalogService $service): JsonResponse
    {
        return response()->json($service->supplyUnits($this->normalize($request)));
    }

    public function materialPrices(PriceSearchRequest $request, PriceResearchService $service): JsonResponse
    {
        return response()->json($service->materialPrices($this->normalize($request)));
    }

    public function servicePrices(PriceSearchRequest $request, PriceResearchService $service): JsonResponse
    {
        return response()->json($service->servicePrices($this->normalize($request)));
    }

    public function uasg(UasgSearchRequest $request, UasgService $service): JsonResponse
    {
        return response()->json($service->search($this->normalize($request)));
    }

    private function normalize(CatalogSearchRequest|PriceSearchRequest|UasgSearchRequest $request): array
    {
        return array_filter(
            $request->validated(),
            static fn ($value) => $value !== null && $value !== ''
        );
    }
}