<?php
namespace project5\Provider;

interface ICanRank
{
    /**
     * @return bool
     */
    public function canRank();

    /**
     * @param IEntity $entity
     * @return bool
     */
    public function isFirst(IEntity $entity);

    /**
     * @param IEntity $entity
     * @return bool
     */
    public function isLast(IEntity $entity);

    /**
     * @param IEntity $entity
     * @return bool
     */
    public function moveUp(IEntity $entity);

    /**
     * @param IEntity $entity
     * @return bool
     */
    public function moveDown(IEntity $entity);

    /**
     * @param IEntity $entity
     * @return bool
     */
    public function moveToTop(IEntity $entity);

    /**
     * @param IEntity $entity
     * @return bool
     */
    public function moveToBottom(IEntity $entity);

    /**
     * @param IEntity $entityA
     * @param IEntity $entityB
     * @return bool
     */
    public function swapRank(IEntity $entityA, IEntity $entityB);

    /**
     * @return ICanRank
     */
    public function sortByRank($reverse = false);
}