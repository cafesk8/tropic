<?php

declare(strict_types=1);

namespace App\Component\Image;

use Doctrine\ORM\Query\ResultSetMapping;
use Shopsys\FrameworkBundle\Component\Image\ImageRepository as BaseImageRepository;

/**
 * @method \App\Component\Image\Image|null findImageByEntity(string $entityName, int $entityId, string|null $type)
 * @method \App\Component\Image\Image getImageByEntity(string $entityName, int $entityId, string|null $type)
 * @method \App\Component\Image\Image[] getImagesByEntityIndexedById(string $entityName, int $entityId, string|null $type)
 * @method \App\Component\Image\Image[] getAllImagesByEntity(string $entityName, int $entityId)
 * @method \App\Component\Image\Image getById(int $imageId)
 * @method \App\Component\Image\Image[] getMainImagesByEntitiesIndexedByEntityId(array $entitiesOrEntityIds, string $entityName)
 */
class ImageRepository extends BaseImageRepository
{
    /**
     * @return int
     */
    public function getHighestImageId(): int
    {
        return (int)$this->em->getConnection()->fetchColumn('SELECT MAX(id) FROM images');
    }

    /**
     * @param int $entityId
     * @param string $entityName
     * @param int $imageId
     * @param string $extension
     * @param int|null $position
     * @param string|null $type
     * @param int|null $pohodaId
     */
    public function saveImageIntoDb(
        int $entityId,
        string $entityName,
        int $imageId,
        string $extension,
        ?int $position = null,
        ?string $type = null,
        ?int $pohodaId = null
    ): void {
        $query = $this->em->createNativeQuery(
            'INSERT INTO images (id, entity_name, entity_id, type, extension, position, modified_at, pohoda_id)
            VALUES (:id, :entity_name, :entity_id, :type, :extension, :position, :modified_at, :pohoda_id)',
            new ResultSetMapping()
        );

        $query->execute([
            'id' => $imageId,
            'entity_name' => $entityName,
            'entity_id' => $entityId,
            'type' => $type,
            'extension' => $extension,
            'position' => $position,
            'modified_at' => new \DateTime(),
            'pohoda_id' => $pohodaId,
        ]);
    }

    /**
     * @param int $startWithId
     */
    public function restartImagesIdsDbSequence(int $startWithId): void
    {
        $this->em->createNativeQuery(sprintf('ALTER SEQUENCE images_id_seq RESTART WITH %d', $startWithId), new ResultSetMapping())->execute();
    }

    /**
     * @param int $pohodaId
     * @return \App\Component\Image\Image|null
     */
    public function findByPohodaId(int $pohodaId): ?Image
    {
        return $this->getImageRepository()->findOneBy(['pohodaId' => $pohodaId]);
    }

    /**
     * @param int $imageId
     * @param int $position
     */
    public function updateImagePosition(int $imageId, int $position): void
    {
        $this->em->createNativeQuery('UPDATE images SET position = :position WHERE id = :id', new ResultSetMapping())->execute([
            'position' => $position,
            'id' => $imageId,
        ]);
    }

    /**
     * @param int[] $currentPohodaImageIdsIndexedByProductId
     * @return int[]
     */
    public function deleteImagesWithNotExistingPohodaId(array $currentPohodaImageIdsIndexedByProductId): array
    {
        $queryBuilder = $this->getImageRepository()
            ->createQueryBuilder('i')
            ->where('i.pohodaId IS NOT NULL')
            ->andWhere('i.entityId = :entityId');

        $imagesToDelete = [];
        foreach ($currentPohodaImageIdsIndexedByProductId as $productId => $currentPohodaIds) {
            $queryParameters = ['entityId' => $productId];
            if (empty($currentPohodaIds)) {
                $imagesToDelete = array_merge($imagesToDelete, $queryBuilder->getQuery()->execute($queryParameters));
            } else {
                $queryParameters['pohodaIds'] = $currentPohodaIds;
                $clonedQueryBuilder = clone $queryBuilder;
                $imagesToDelete = array_merge($imagesToDelete, $clonedQueryBuilder
                    ->andWhere('i.pohodaId NOT IN (:pohodaIds)')
                    ->getQuery()
                    ->execute($queryParameters));
            }
        }

        $deletedImageIds = [];
        foreach ($imagesToDelete as $imageToDelete) {
            $deletedImageIds[] = $imageToDelete->getId();
            $this->em->remove($imageToDelete);
        }

        return $deletedImageIds;
    }
}
