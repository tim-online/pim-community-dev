<?php

namespace Poc\Bundle\PatchSetBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ImportPatchSetCommand extends ContainerAwareCommand
{
    private $valuesCode;
    private $associationCode;

    private $familyIds = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('poc:patch-set:import')
            ->addArgument('file', InputArgument::REQUIRED)
            ->setDescription('Import product using patch set');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('file');

        $file = fopen($filename, 'r');

        $conn = $this->getContainer()->get('database_connection');

        while ($line = fgets($file)) {
            $patch = json_decode($line, true);
            if ($this->validatePatch($patch, $conn)) {
                $this->applyPatch($patch, $conn);
            }
        }
    }

    private function validatePatch(array $patch, Connection $conn)
    {
        $isValid = true;

        foreach($patch as $propertyKey => $propertyValue) {
        }

        return $isValid;
    }

    private function applyPatch(array $patch, Connection $conn)
    {
        $conn->beginTransaction();
        //try {
            $this->applyOnMainTable($patch, $conn);

            $this->applyCategories($patch, $conn);
            $this->applyUniqueData($patch, $conn);

            // if patch is applied on a field included in completeness
            // then $this->generateCompleteness($patch, $conn);

            $conn->commit();

            $this->indexFromPatch($patch, $conn);
            /*
        } catch (\Exception $e) {
            $conn->rollback();
            echo "Exception ".$e->getMessage()." occured on patch ". $patch['identifier'] .".\n";
            throw $e;
        }*/
    }

    private function applyOnMainTable(array $patch, Connection $conn)
    {
        $productRow = $conn->fetchAssoc("SELECT * FROM pim_catalog_product WHERE identifier = ?", [$patch['identifier']]);

        if (!$productRow) {
            $familyId = $this->getFamilyIdFromCode($patch['family'], $conn);

            $values = $this->patchValues([], $patch);

            $conn->executeUpdate(
                "INSERT INTO pim_catalog_product(identifier, family, is_enabled, raw_values, created) VALUES(?, ?, ?, ?, NOW())",
                [
                    $patch['identifier'],
                    $familyId,
                    $patch['enabled'] ? 1 : 0,
                    json_encode($values)
                ]
            );
        } else {
            $values = json_decode($productRow['raw_values'], true);
            $values = $this->patchValues($values, $patch);
            $conn->executeUpdate(
                "UPDATE pim_catalog_product SET is_enabled = ?, raw_values = ?, updated = NOW() WHERE identifier = ?",
                [
                   $patch['enabled'] ? 1 : 0,
                   json_encode($values),
                   $patch['identifier']
                ]
            );
        }
    }

    private function patchValues(array $values, array $patch): array
    {
        $patchedValues = $values;

        echo "DEBUG values:". print_r($values, true)."\n";
        echo "DEBUG patch:". print_r($patch, true)."\n";

        foreach($patch['values'] as $valueKey => $valuePatch) {
            foreach($valuePatch as $valueData) {
                $locale = $valueData['locale'];
                if (empty($locale)) {
                    $locale = "<all_locales>";
                }
                $channel = $valueData['channel'];
                if (empty($channel)) {
                    $channel = "<all_locales>";
                }

                $patchedValues[$valueKey][$locale][$channel] = $valueData['data'];
            }
        }

        return $patchedValues;
    }

    private function getFamilyIdFromCode(string $familyCode, Connection $conn)
    {
        if (!isset($familyId[$familyCode])) {
            $familyId[$familyCode] = $conn->fetchColumn("SELECT id FROM pim_catalog_family WHERE code = ?", [$familyCode]);
        }

        return $familyId[$familyCode];
    }

    private function applyCategories(array $patch, Connection $conn) {
        if (!isset($patch['categories'])) {
            return;
        }

        // Extract categories ids

        // Remove all categories relations and insert categories
    }

    private function applyUniqueData(array $patch, Connection $conn) {
        if (!isset($patch['values'])) {
            return;
        }

        // Extract values that must be unique

        // Remove all unique values and insert new ones
    }

    private function indexFromPatch(array $patch, Connection $conn)
    {
    }
}
