<?php

/**
 * Class ilCardExporter
 * @author Oskar Truffer <ot@studer-raimann.ch>
 */
class ilCardExporter extends ilXmlExporter
{
    public function getXmlExportHeadDependencies(/* string */ $a_entity, /* string */ $a_target_release, /* array */ $a_ids) /* : array */
    {
        // collect the files to export
        // ref_id
        // thumbnail
        // https://docu.ilias.de/ilias.php?baseClass=illmpresentationgui&obj_id=32894&ref_id=42
        // https://github.com/ILIAS-eLearning/TestPageComponent/blob/master/classes/class.ilTestPageComponentExporter.php

        $ref_id = false;
        $file_id = false;
        foreach ($a_ids as $id) {
            $properties = ilPageComponentPluginExporter::getPCProperties($id);
            if (isset($properties['ref_id'])) {
                $ref_id = $properties['ref_id'];
            }
            if (isset($properties['thumbnail'])) {
                $file_id = $properties['thumbnail'];
            }
        }

        $deps = [];

        $components = [
            "copa" => "Modules/ContentPage",
            "lm" => "Modules/LearningModule",
            "file" => "Modules/File",
            "sahs" => "Modules/Scorm2004",
            "htlm" => "Modules/HTMLLearningModule",
            "tst" => "Modules/Test",
            "fold" => "Modules/Folder",
            "xjit" => "",
            "exc" => "Modules/Exercise",
            "frm" => "Modules/Forum",
        ];

        // add the files as dependencies
        if (!empty(($ref_id))) {
            $obj = ilObjectFactory::getInstanceByRefId($ref_id);
            if (!empty($obj)) {
                $obj_id = $obj->getId();
                $type = $obj->getType();
    
                if (!empty($components[$type])) {
                    $deps[] = array(
                        "component" => $components[$type],
                        "entity" => $type,
                        "ids" => $ref_id
                    );
                }
            }
        }
        if (!empty(($file_id))) {
            $deps[] = array(
                "component" => $components["file"],
                "entity" => "file",
                "ids" => $file_id
            );
        }

        return $deps;
    }

    /**
     * Get xml representation
     * @param string        entity
     * @param string        schema version
     * @param string        id
     * @return    string        xml string
     */
    public function getXmlRepresentation(/* string */ $a_entity, /* string */ $a_schema_version, /* string */ $a_id) /* : string */
    {
        return true;
        $obj_id = intval(explode(":", $a_id)[1]);
        $ref_ids = ilObject::_getAllReferences($obj_id);
        $ref_id = array_shift($ref_ids);

        if (empty($ref_id)) return false;

        $obj = ilObjectFactory::getInstanceByRefId($ref_id);
        $title = $obj->getTitle();
        $description = $obj->getDescription();
        $layout = "square";
        $starting_date = false;
        $duration = false;

        $writer = new ilXmlWriter();
        $writer->xmlStartTag("pcard");
        $writer->xmlElement("title", null, $title);
        $writer->xmlElement("description", null, $description);
        $writer->xmlEndTag("pcard");

        return $writer->xmlDumpMem(false);
    }

    public function init() : void
    {
        // TODO: Implement init() method.
    }

    /**
     * Get tail dependencies
     * @param string        entity
     * @param string        target release
     * @param array        ids
     * @return        array        array of array with keys "component", entity", "ids"
     */
    public function getXmlExportTailDependencies(/* string */ $a_entity, /* string */ $a_target_release, /* array */ $a_ids) /* : array */
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
     * @param string $a_entity
     * @return string[][]
     */
    public function getValidSchemaVersions(/* string */ $a_entity) /* : array */
    {
        return array(
            "5.2.0" => array(
                "namespace" => "http://www.ilias.de/Plugins/Card/md/5_2",
                "xsd_file" => "ilias_md_5_2.xsd",
                "min" => "5.2.0",
                "max" => ""
            )
        );
    }
}