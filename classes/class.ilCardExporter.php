<?php

/**
 * Class ilCardExporter
 * @author Oskar Truffer <ot@studer-raimann.ch>
 */
class ilCardExporter extends ilXmlExporter
{

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
        var_dump($a_id, $obj_id, $ref_id); die();

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