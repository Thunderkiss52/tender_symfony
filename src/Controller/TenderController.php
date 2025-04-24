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
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TenderController extends AbstractController
{
    private function log(string $message): void
    {
        $logFile = __DIR__ . '/../../var/log/app.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }

    #[Route('/tenders', name: 'create_tender', methods: ['POST'])]
    public function createTender(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $jsonData = $request->getContent();
        $this->log('Received JSON data: ' . $jsonData);

        try {
            $tender = $serializer->deserialize($jsonData, Tender::class, 'json', [
                'groups' => 'tender',
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
            ]);
        } catch (\Exception $e) {
            $this->log('Deserialization failed: ' . $e->getMessage());
            return $this->json(['error' => 'Invalid JSON data', 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $debugData = [
            'externalCode' => $tender->getExternalCode(),
            'number' => $tender->getNumber(),
            'status' => $tender->getStatus(),
            'name' => $tender->getName(),
            'date' => $tender->getDate() ? $tender->getDate()->format('Y-m-d H:i:s') : null,
        ];
        $this->log('Deserialized Tender: ' . json_encode($debugData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $errors = $validator->validate($tender);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            $this->log('Validation failed: ' . json_encode($errorMessages));
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $now = new \DateTime();
        $tender->setCreatedAt($now);
        $tender->setUpdatedAt($now);

        $entityManager->persist($tender);
        $entityManager->flush();

        $this->log('Tender created with ID: ' . $tender->getId());

        return $this->json($tender, Response::HTTP_CREATED);
    }

    #[Route('/tenders/{id}', name: 'get_tender', methods: ['GET'])]
    public function getTender(string $id, EntityManagerInterface $entityManager): Response
    {
        if (!ctype_digit($id) || (int)$id <= 0) {
            $this->log('Invalid ID format: ' . $id);
            return $this->json(['error' => 'Invalid ID format'], Response::HTTP_BAD_REQUEST);
        }

        $tender = $entityManager->getRepository(Tender::class)->find((int)$id);

        if (!$tender) {
            $this->log('Tender not found for ID: ' . $id);
            return $this->json(['error' => 'Tender not found'], Response::HTTP_NOT_FOUND);
        }

        $this->log('Tender retrieved with ID: ' . $id);
        return $this->json($tender, Response::HTTP_OK);
    }

    #[Route('/tenders', name: 'tenders_list', methods: ['GET'])]
    public function list(Request $request, TenderRepository $tenderRepository): JsonResponse
    {
        $filters = [
            'externalCode' => $request->query->get('externalCode'),
            'number' => $request->query->get('number'),
            'status' => $request->query->get('status'),
            'name' => $request->query->get('name'),
            'date' => $request->query->get('date'),
        ];

        if (!empty($filters['date'])) {
            try {
                $date = new \DateTime($filters['date']);
                $filters['date'] = $date->format('Y-m-d H:i:s'); // Для логов и репозитория
            } catch (\Exception $e) {
                $this->log('Invalid date filter: ' . $filters['date']);
                unset($filters['date']); // Пропускаем фильтр, если дата некорректна
            }
        }

        $filters = array_filter($filters, fn($value) => !is_null($value) && $value !== '');

        $this->log('Fetching tenders with filters: ' . json_encode($filters, JSON_UNESCAPED_UNICODE));

        $data = $tenderRepository->findTendersWithFilters($filters);

        $this->log('Found ' . count($data) . ' tenders');
        return $this->json($data);
    }
}