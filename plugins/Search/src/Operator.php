<?php
/**
 * Licensed under The GPL-3.0 License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @since    2.0.0
 * @author   Christopher Castro <chris@quickapps.es>
 * @link     http://www.quickappscms.org
 * @license  http://opensource.org/licenses/gpl-3.0.html GPL-3.0 License
 */
namespace Search;

use Cake\Core\InstanceConfigTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Search\Token;

/**
 * Base operator class, every operator handler should extends this class.
 *
 */
abstract class Operator
{

    use InstanceConfigTrait;

    /**
     * Default configuration for this operator.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * The table for which handle this operator.
     *
     * @var \Cake\ORM\Table
     */
    protected $_table = null;

    /**
     * Operator constructor.
     *
     * @param \Cake\ORM\Table $table The table for which handle this operator
     * @param array $config Additional configuration options for this
     *  particular operator
     */
    public function __construct(Table $table, $config = [])
    {
        $this->_table = $table;
        $this->config($config);
    }

    /**
     * Alters the given query and applies this operator's filter conditions.
     *
     * @param \Cake\ORM\Query $query The query to alter
     * @param \Search\Token $token Token representing this operator. Frequently used
     *  methods are:
     *  - Token::value(): The value for this operator, that is whatever comes
     *    after `:` symbol. e.g. `JohnLocke` for criteria `author:JohnLocke`
     *  - Token::negated(): True if user has negated this operator using the `-`
     *    symbol. e.g.: `-author:JohnLocke`. False otherwise.
     *  - Token::where(): Possible values are "or", "and" and null. Indicates the
     *    type of WHERE condition that should be used when scoping using this token,
     *    for instance, `OR author:JohnLocke` will return `or`. But,
     *    `AND author:JohnLocke` will return `and`. By default it is set
     *    to NULL. This allows you to use Query::andWhere() and Query::orWhere()
     *    methods as needed.
     * @return \Cake\ORM\Query Altered query
     */
    abstract public function scope(Query $query, Token $token);
}
