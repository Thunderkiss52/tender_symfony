<?php

namespace App\Controller;

use App\Repository\TenderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Tender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;


final class TenderController extends AbstractController
{

    #[Route('/tenders', name: 'create_tender', methods: ['POST'])]
    public function createTender(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): Response
    {
        $jsonData = $request->getContent();
        try {
            $tender = $serializer->deserialize($jsonData, Tender::class, 'json', ['groups' => 'tender']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid JSON data', 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($tender);
        $entityManager->flush();

        return $this->json($tender, Response::HTTP_CREATED);
    }

    #[Route('/tenders/{id}', name: 'get_tender', methods: ['GET'])]
    public function getTender(int $id, EntityManagerInterface $entityManager): Response
    {
        $tender = $entityManager->getRepository(Tender::class)->find($id);

        if (!$tender) {
            return $this->json(['error' => 'Tender not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($tender, Response::HTTP_OK);
    }

    #[Route('/tenders', name: 'tenders_list', methods: ['GET'])]
    public function list(Request $request, TenderRepository $tenderRepository): JsonResponse
    {
        // Извлекаем фильтры из GET-параметров
        $filters = [
            'externalCode' => $request->query->get('externalCode'),
            'number' => $request->query->get('number'),
            'status' => $request->query->get('status'),
            'name' => $request->query->get('name'),
            'date' => $request->query->get('date'),
        ];

        // Удаляем пустые фильтры
        $filters = array_filter($filters, fn($value) => !is_null($value) && $value !== '');

        // Получаем данные напрямую в нужном формате
        $data = $tenderRepository->findTendersWithFilters($filters);

        return $this->json($data);
    }

}