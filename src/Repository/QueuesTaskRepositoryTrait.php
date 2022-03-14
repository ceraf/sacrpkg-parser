<?php

/*
 * This file is part of the Sacrpkg ParserBundle package.
 *
 * (c) Oleg Bruyako <jonsm2184@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace sacrpkg\ParserBundle\Repository;

trait QueuesTaskRepositoryTrait
{
    public function isNeedRun(int $freq, int $timeout): bool
    {
        $freq_dt = new \DateTime('-'.$freq.' days');
        $timeout_dt = new \DateTime('-'.$timeout.' days');
        
        $res = $this->createQueryBuilder('q')
            ->andWhere('((q.status = :proc_status) AND (q.created_at > :timeout_dt)) OR ((q.status = :success_status) AND (q.created_at > :freq_dt))')
            ->setParameter('proc_status', 'in_process')
            ->setParameter('success_status', 'success')
            ->setParameter('timeout_dt', $timeout_dt)
            ->setParameter('freq_dt', $freq_dt)
            ->getQuery()
            ->getResult()
        ;
  //      var_dump(count($res) > 0);
        
        return (count($res) == 0);
    }
    
    public function createTask($total_items = 0): int
    {
        $entity_class = $this->getEntityClass();
        
        $entity = new ($entity_class)();
        $entity->setCode($entity_class::CODE_SEARCH)
            ->setStatus($entity_class::STATUS_INPROGRESS)
            ->setPid(getmypid())
            ->setNumitemsbefore($total_items)
        ;
        $this->_em->persist($entity);
        $this->_em->flush($entity);
        $id = $entity->getId();

        return $id;
    }
    
    public function getSignalTask(int $task_id): string
    {
        $item = $this->createQueryBuilder('q')
            ->select('q.signal')
            ->andWhere('q.id = :task_id')
            ->setParameter('task_id', $task_id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        
        return $item['signal'] ?? '';
    }
    
    public function endTask(int $task_id, $total_items = 0): void
    {
        $entity_class = $this->getEntityClass();
        
        $task = $this->find($task_id);
        $task->setStatus($entity_class::STATUS_SUCCESS)
            ->setFinishedAt(new \DateTime('now'))
            ->setNumitemsafter($total_items)
        ;
        $this->_em->flush($task);
    }

    public function errorTask(int $task_id, $total_items = 0): void
    {
        $entity_class = $this->getEntityClass();
        
        $task = $this->find($task_id);
        $task->setStatus($entity_class::STATUS_ERROR)
            ->setFinishedAt(new \DateTime('now'))
            ->setNumitemsafter($total_items)
        ;
        $this->_em->flush($task);
    }
    
    public function getLIst(): ?array
    {
        $res = $this->createQueryBuilder('q')
            ->select(
                    'q.id',
                    'q.created_at', 
                    'q.updated_at', 
                    'q.finished_at', 
                    'q.code', 
                    'q.params', 
                    'q.status', 
                    'q.pid', 
                    'q.signal', 
                    'q.signal', 
                    'q.numitemsbefore', 
                    'q.numitemsafter', 
                )
            ->orderBy('q.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
        
        if ($res) {
            foreach ($res as &$item) {
                $item['created_at'] = $item['created_at']->format('Y-m-d H:i');
                if ($item['updated_at']) {
                    $item['updated_at'] = $item['updated_at']->format('Y-m-d H:i');
                }
                if ($item['finished_at']) {
                    $item['finished_at'] = $item['finished_at']->format('Y-m-d H:i');
                }
            }
        }
        
        return $res;
    }
}
