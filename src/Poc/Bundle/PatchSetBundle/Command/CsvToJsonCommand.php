<?php

namespace Poc\Bundle\PatchSetBundle\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CsvToJsonCommand extends ContainerAwareCommand
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
            ->setName('poc:patch-set:csv-to-json')
            ->addArgument('csv', InputArgument::REQUIRED, 'Input CSV file')
            ->addArgument('json', InputArgument::REQUIRED, 'Output JSON streamed file')
            ->setDescription('Convert CSV files to JSON streamed file');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csvFilename = $input->getArgument("csv");
        $jsonFilename = $input->getArgument("json");

        $conn = $this->getContainer()->get('database_connection');

        $csvFile = fopen($csvFilename, 'r');

        $headers = fgetcsv($csvFile, 0, ";");

        file_put_contents($jsonFilename,"");

        while($line = fgetcsv($csvFile, 0, ";")) {
            $productLine = array_combine($headers, $line);

            $productPatch = [];
            $productPatch['identifier'] = $productLine['sku'];

            $productPatch['family'] = $productLine['family'];
            unset($productLine['family']);

            $productPatch['parent'] = $productLine['parent'];
            unset($productLine['parent']);

            $productPatch['categories'] = explode(',',$productLine['categories']);
            unset($productLine['categories']);

            $productPatch['groups'] = explode(',',$productLine['groups']);
            unset($productLine['groups']);

            $productPatch['enabled'] = !$productLine['enabled'] === "0";
            unset($productLine['enabled']);

            foreach ($productLine as $propertyKey => $propertyValue) {
                if (strstr($propertyKey, '-') === false) {
                    $productPatch['values'][$propertyKey][] = [
                        "data" => $propertyValue,
                        "locale" => null,
                        "channel" => null
                    ];
                }
            }

            file_put_contents($jsonFilename, json_encode($productPatch)."\n", FILE_APPEND);
        }

    }
}
