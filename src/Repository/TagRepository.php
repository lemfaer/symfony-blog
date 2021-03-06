<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Parses tags string
     *
     * @param string $tag_string string of tags, separated by `,`
     * @return array
     */
    public function tags($tag_string)
    {
        $regex = "/((?![\w,-_\040]).)+/";
        $tag_string = preg_replace($regex, "", $tag_string);
        $tag_string = mb_strtolower($tag_string);
        $tags = explode(",", $tag_string);
        $tags = array_map("trim", $tags);
        $tags = array_unique($tags);

        return $tags;
    }

    /**
     * Creates tag string from tag objects
     *
     * @param Tag[] $tag_objects array of tag entities
     *
     * @return string
     */
    public function tag_string($tag_objects)
    {
        $tag_names = array_column($tag_objects ?: [], "name");
        $tag_string = implode(", ", $tag_names);

        return $tag_string;
    }

    /**
     * Creates tags in db if they not exists
     *
     * @param string $tag_string string of tags, separated by `,`
     * @param int $limit limit of tags to add/return as result
     *
     * @return array
     */
    public function add_tags($tag_string, $limit = 5)
    {
        $tags = $this->tags($tag_string);
        $entities = $this->get_tags($tag_string, $limit);
        $em = $this->getEntityManager();

        if (count($entities) < $limit) {
            $exists = array_column($entities, "name");
            $left = $limit - count($exists);
            $nexists = array_diff($tags, $exists);
            $nexists = array_values($nexists);
            $nexists = array_slice($nexists, 0, $left);

            foreach ($nexists as $name) {
                $entity = new Tag;
                $entity->name = $name;
                $entities[] = $entity;
                $em->persist($entity);
            }

            $em->flush();
        }

        return $entities;
    }

    /**
     * Gets tags from db by tag string
     *
     * @param string $tag_string string of tags, separated by `,`
     * @param int $limit limit of tags to load
     * @param int $offset offset count
     *
     * @return array
     */
    public function get_tags($tag_string, $limit = 5, $offset = 0)
    {
        $tags = $this->tags($tag_string);

        $query = $this
            ->createQueryBuilder("t")
            ->where("t.name IN (:tags)")
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameter("tags", $tags)
            ->getQuery();

        return $query->execute();
    }
}
