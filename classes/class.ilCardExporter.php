<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
 * Exporter class for the Card Plugin
 * @author Roberto Pasini <bonjour@kalamun.net>
 */
class ilCardExporter extends ilPageComponentPluginExporter
{
    public function init() : void
    {
    }

    /**
     * Get head dependencies
     * @param string        entity
     * @param string        target release
     * @param array        ids
     * @return        array        array of array with keys "component", entity", "ids"
     */
    public function getXmlExportHeadDependencies(string $a_entity, string $a_target_release, array $a_ids) : array
    {
        // collect the files to export
        $file_ids = array();
        foreach ($a_ids as $id) {
            $properties = self::getPCProperties($id);
            if (isset($properties['page_file'])) {
                $file_ids[] = $properties['page_file'];
            }
        }

        // add the files as dependencies
        if (!empty(($file_ids))) {
            return array(
                array(
                    "component" => "Modules/File",
                    "entity" => "file",
                    "ids" => $file_ids
                )
            );
        }

        return array();
    }

    /**
     * Get xml representation
     * @param string        entity
     * @param string        schema version
     * @param string        id
     * @return    string        xml string
     */
    public function getXmlRepresentation(string $a_entity, string $a_schema_version, string $a_id) : string
    {
        global $DIC;

        /** @var ilComponentFactory $component_factory */
        $component_factory = $DIC["component.factory"];

        /** @var ilCardPlugin $plugin */
        $plugin = $component_factory->getPlugin("pctpc");

        $properties = self::getPCProperties($a_id);
        $data = $plugin->getData($properties['additional_data_id']);
        return '<data>' . htmlentities($data) . '</data>';
    }

    /**
     * Get tail dependencies
     * @param string        entity
     * @param string        target release
     * @param array        ids
     * @return        array        array of array with keys "component", entity", "ids"
     */
    public function getXmlExportTailDependencies(string $a_entity, string $a_target_release, array $a_ids) : array
    {
        return array();
    }

    /**
     * Returns schema versions that the component can export to.
     * ILIAS chooses the first one, that has min/max constraints which
     * fit to the target release. Please put the newest on top. Example:
     *        return array (
     *        "4.1.0" => array(
     *            "namespace" => "http://www.ilias.de/Services/MetaData/md/4_1",
     *            "xsd_file" => "ilias_md_4_1.xsd",
     *            "min" => "4.1.0",
     *            "max" => "")
     *        );
     */
    public function getValidSchemaVersions(string $a_entity) : array
    {
        return array(
            '5.3.0' => array(
                'namespace' => 'http://www.ilias.de/',
                //'xsd_file'     => 'pctpc_5_3.xsd',
                'uses_dataset' => false,
                'min' => '5.3.0',
                'max' => ''
            )
        );
    }
}