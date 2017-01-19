<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\Pv\Repo\Entity\Group;

use Praxigento\Pv\Data\Entity\Group\Price as Entity;

interface IPrice
    extends \Praxigento\Core\Repo\IEntity
{
    /**
     * @param array|Entity $data
     * @return Entity
     */
    public function create($data);

    /**
     * Generic method to get data from repository.
     *
     * @param null $where
     * @param null $order
     * @param null $limit
     * @param null $offset
     * @param null $columns
     * @param null $group
     * @param null $having
     * @return Entity[] Found data or empty array if no data found.
     */
    public function get(
        $where = null,
        $order = null,
        $limit = null,
        $offset = null,
        $columns = null,
        $group = null,
        $having = null
    );

    /**
     * Get the data instance by ID.
     *
     * @param int $id
     * @return Entity|bool Found instance data or 'false'
     */
    public function getById($id);

}