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

trait QueuesProcessRepositoryTrait
{
    public function createProcess(int $task_id, string $str, int $pos): int
    {
        $id = null;
        $entity_class = $this->getEntityClass();
        
        $task = $this->_em->getRepository('App:QueuesTask')
                ->find($task_id);
        
        if ($task) {
            $entity = new ($entity_class)();
            $entity->setParams(['str' => $str, 'pos' => $pos])
                ->setStatus($entity_class::STATUS_INPROGRESS)
                ->setPid(getmypid())
                ->setTask($task)
                ->setStartedAt(new \DateTime('now'))
            ;
            $this->_em->persist($entity);
            $this->_em->flush($entity);
            $id = $entity->getId();            
        }

        return $id;
    }

    public function endProcess(int $proc_id): void
    {
        $entity_class = $this->getEntityClass();
        
        $proc = $this->find($proc_id);
        $proc->setStatus($entity_class::STATUS_SUCCESS)
            ->setFinishedAt(new \DateTime('now'))
        ;
        $this->_em->flush($proc);
    }

    public function errorProcess(int $proc_id, $error): void
    {
        $entity_class = $this->getEntityClass();
        
        $proc = $this->find($proc_id);
        $proc->setStatus($entity_class::STATUS_ERROR)
            ->setMessage($error)
            ->setFinishedAt(new \DateTime('now'))
        ;
        $this->_em->flush($proc);
    }
}
