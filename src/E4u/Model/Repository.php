<?php
namespace E4u\Model;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\QueryBuilder,
    Doctrine\ORM\Query\Expr;

class Repository extends EntityRepository
{
    const string 
        PATTERN_PHRASE_ID = '/^(ID:|#)([\d ,]+)$/i',
        PATTERN_FIELD_ID = '/^\w+\.id$/i';

    public function notEqOrNull(mixed $x, mixed $y, QueryBuilder $qb): Expr\Orx
    {
        $ex = $qb->expr();
        return $ex->orX(
            $ex->neq($x, $y),
            $ex->isNull($x)
        );
    }

    protected function preparePhrase(string $phrase): string
    {
        $phrase = '%'.trim($phrase).'%';
        $phrase = str_replace('-', '%', $phrase);
        $phrase = str_replace(' ', '%', $phrase);
        $phrase = str_replace('*', '%', $phrase);
        return preg_replace('/%+/', '%', $phrase);
    }

    private function _getIDField(array $fields): ?string
    {
        return array_find($fields, fn($field) => preg_match(self::PATTERN_FIELD_ID, $field));

    }

    protected function wherePhrase(string $phrase, array $fields, QueryBuilder $qb): Expr\Func|Expr\Orx
    {
        $ex = $qb->expr();
        $phrase = trim($phrase);

        // if the search phrase is something like #123456 or ID:123456
        // and we have id in field list, we narrow the search to id-only
        if (preg_match(self::PATTERN_PHRASE_ID, $phrase, $regs)
            && ($alias = $this->_getIDField($fields))) {
            return $ex->in($alias, $regs[2]);
        }

        $phrase = $ex->literal($this->preparePhrase($phrase));
        $orX = $ex->orX();

        foreach ($fields as $field) {
            $orX->add($ex->like($field, $phrase));
        }

        return $orX;
    }
}