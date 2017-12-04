<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace project5\Propel\Platform;

use Propel\Generator\Model\Diff\TableDiff;
use Propel\Generator\Platform\MysqlPlatform as PropelMysqlPlatform;

/**
 * MySql PlatformInterface implementation.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 */
class MysqlPlatform extends PropelMysqlPlatform
{


    public function getModifyTableDDL2(TableDiff $tableDiff)
    {
        $alterTableStatements = '';

        $toTable = $tableDiff->getToTable();

        // drop indices, foreign keys
        foreach ($tableDiff->getRemovedFks() as $fk) {
            $alterTableStatements .= $this->getDropForeignKeyDDL($fk);
        }
        foreach ($tableDiff->getModifiedFks() as $fkModification) {
            list($fromFk) = $fkModification;
            $alterTableStatements .= $this->getDropForeignKeyDDL($fromFk);
        }
        foreach ($tableDiff->getRemovedIndices() as $index) {
            $alterTableStatements .= $this->getDropIndexDDL($index);
        }
        foreach ($tableDiff->getModifiedIndices() as $indexModification) {
            list($fromIndex) = $indexModification;
            $alterTableStatements .= $this->getDropIndexDDL($fromIndex);
        }

        // alter table structure
        if ($tableDiff->hasModifiedPk()) {
            $alterTableStatements .= $this->getDropPrimaryKeyDDL($tableDiff->getFromTable());
        }
        foreach ($tableDiff->getRenamedColumns() as $columnRenaming) {
            $alterTableStatements .= $this->getRenameColumnDDL($columnRenaming[0], $columnRenaming[1]);
        }
        if ($modifiedColumns = $tableDiff->getModifiedColumns()) {
            $alterTableStatements .= $this->getModifyColumnsDDL($modifiedColumns);
        }
        if ($addedColumns = $tableDiff->getAddedColumns()) {
            $alterTableStatements .= $this->getAddColumnsDDL($addedColumns);
        }
        foreach ($tableDiff->getRemovedColumns() as $column) {
            $alterTableStatements .= $this->getRemoveColumnDDL($column);
        }

        // add new indices and foreign keys
        if ($tableDiff->hasModifiedPk()) {
            $alterTableStatements .= $this->getAddPrimaryKeyDDL($tableDiff->getToTable());
        }

        // create indices, foreign keys
        foreach ($tableDiff->getModifiedIndices() as $indexModification) {
            list($oldIndex, $toIndex) = $indexModification;
            $alterTableStatements .= $this->getAddIndexDDL($toIndex);
        }
        foreach ($tableDiff->getAddedIndices() as $index) {
            $alterTableStatements .= $this->getAddIndexDDL($index);
        }
        foreach ($tableDiff->getModifiedFks() as $fkModification) {
            list(, $toFk) = $fkModification;
            $alterTableStatements .= $this->getAddForeignKeyDDL($toFk);
        }
        foreach ($tableDiff->getAddedFks() as $fk) {
            $alterTableStatements .= $this->getAddForeignKeyDDL($fk);
        }


        $ret = '';
        if (trim($alterTableStatements)) {
            //merge all changes into one command. This prevents https://github.com/propelorm/Propel2/issues/1115

            $changes = explode(';', $alterTableStatements);
            $dropFragments = []; $changeFragments = [];
            foreach ($changes as $change) {
                if (trim($change)) {
                    $part_sql = preg_replace(
                        sprintf('/ALTER TABLE %s /', $this->quoteIdentifier($toTable->getName())),
                        "\n\n  ",
                        trim($change)
                    );

                    if (strpos($part_sql, 'DROP FOREIGN KEY') !== false) {
                        $dropFragments[] = $part_sql;
                    } else {
                        $changeFragments[] = $part_sql;
                    }
                }
            }

            if ($dropFragments) {
                $ret .= sprintf("
ALTER TABLE %s%s;
",
                    $this->quoteIdentifier($toTable->getName()), implode(',', $dropFragments)
                );
            }

            $ret .= sprintf("
ALTER TABLE %s%s;
",
                $this->quoteIdentifier($toTable->getName()), implode(',', $changeFragments)
            );
        }

        return $ret;
    }
}
