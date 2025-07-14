# local_scormresource

The plugin will help to insert scorm resource data from external source.

## Features

- The plugin accept the corse id ,sectionid, scorm name , folder name and base64zip of scorm package and creates scorm under the specified course.


## Requirements

- Moodle version: 4.5 or higher
- PHP version: 8.3 or higher

## Installation

1. Place the plugin folder into `moodle/local/scormresource/` or install the plugin using the zip
2. Visit the **Site administration > Notifications** page to complete installation.

## Configuration

Add the funtion mod_scorm_insert_scorm_resource into the external service.

## Usage

Use the end point mod_scorm_insert_scorm_resource to insert the scorm  data. Input parameters are courseid,section,scormname,foldername,base64Zip

## Support

For bugs or feature requests, open an issue in the repository or contact the maintainer.

## License

NHS England.
