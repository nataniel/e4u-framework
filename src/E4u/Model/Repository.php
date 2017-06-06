<?php
namespace E4u\Model;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\QueryBuilder;

class Repository extends EntityRepository
{
    const PATTERN_PHRASE_ID = '/^(ID:|#)(\d+)$/i';
    const PATTERN_FIELD_ID = '/^\w+\.id$/i';

    /**
     *
     * @param mixed $x
     * @param mixed $y
     * @param QueryBuilder $qb
     * @return \Doctrine\ORM\Query\Expr\Orx
     */
    public function notEqOrNull($x, $y, $qb)
    {
        $ex = $qb->expr();
        return $ex->orX(
            $ex->neq($x, $y),
            $ex->isNull($x)
        );
    }

    /**
     * @param string $phrase
     * @return string
     */
    protected function preparePhrase($phrase)
    {
        $phrase = '%'.trim($phrase).'%';
        $phrase = str_replace('-', '%', $phrase);
        $phrase = str_replace(' ', '%', $phrase);
        $phrase = str_replace('*', '%', $phrase);
        $phrase = preg_replace('/%+/', '%', $phrase);
        return $phrase;
    }

    /**
     * @param  array $fields
     * @return string|null
     */
    private function _getIDField($fields)
    {
        foreach ($fields as $field) {
            if (preg_match(self::PATTERN_FIELD_ID, $field)) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @param string $phrase
     * @param array $fields
     * @param QueryBuilder $qb
     * @return \Doctrine\ORM\Query\Expr\Orx|\Doctrine\ORM\Query\Expr\Comparison
     */
    protected function wherePhrase($phrase, $fields, $qb)
    {
        $ex = $qb->expr();

        // if the search phrase is something like #123456 or ID:123456
        // and we have id in field list, we narrow the search to id-only
        if (preg_match(self::PATTERN_PHRASE_ID, $phrase, $regs)
            && ($alias = $this->_getIDField($fields))) {
            return $ex->eq($alias, $regs[2]);
        }

        $phrase = $ex->literal($this->preparePhrase($phrase));
        $orX = $ex->orX();

        foreach ($fields as $field) {
            $orX->add($ex->like($field, $phrase));
        }

        return $orX;
    }
}