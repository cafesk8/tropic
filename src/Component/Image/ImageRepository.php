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
     * @param int[] $currentPohodaIds
     * @param int[] $productIds
     * @return array
     */
    public function deleteImagesWithNotExistingPohodaId(array $currentPohodaIds, array $productIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping
            ->addScalarResult('id', 'id')
            ->addScalarResult('extension', 'extension');
        $imageIdsToDelete = $this->em->createNativeQuery('SELECT id, extension FROM images WHERE pohoda_id IS NOT NULL AND pohoda_id NOT IN (:pohodaIds) AND entity_id IN (:entityIds)', $resultSetMapping)->execute([
            'pohodaIds' => $currentPohodaIds,
            'entityIds' => $productIds,
        ]);
        $this->em->createNativeQuery('DELETE FROM images WHERE pohoda_id IS NOT NULL AND pohoda_id NOT IN (:pohodaIds) AND entity_id IN (:entityIds)', new ResultSetMapping())->execute([
            'pohodaIds' => $currentPohodaIds,
            'entityIds' => $productIds,
        ]);

        return $imageIdsToDelete;
    }
}
