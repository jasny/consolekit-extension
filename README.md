ConsoleKit extension
--------------------

An extension to [maximebf's ConsoleKit](https://github.com/maximebf/ConsoleKit)

 * Help - Style help output similar to symfony/console and add support for @example tag.
 * Table widget - Output tabbed (help style) or framed tables.


## Installation

Install the ConsoleKit extension using Composer with the following requirement:

    {
        "require": {
            "maximebf/consolekit": "1.*",
            "jasny/consolekit-extension": "1.*"            
        }
    }


## Usage

### Help

    $console = new ConsoleKit\Console();
    $console->addCommand('\\Jasny\\ConsoleKit\\HelpCommand', 'help', true);
    

### Table widget

Options (booleans):
  * `headers`    - First row contains header (default: false)
  * `border`     - Draw a border around the table (default: true)
  * `frame`      - Draw a frame between the columns of the table (default: true)
  * `skipEmpty`  - Skip empty columns (default: false)
    
    $rows = array(
        array('Name', 'Occupation', 'Country'),
        array('John Doe', 'Plummer', 'Netherlands'),
        array('Joe Fisher', 'Cook', 'France'),
        array('Jack Black', 'Actor', 'USA')
    );
    
    $table = new Jasny\ConsoleKit\Widgets\Table($textwriter, $rows, array('headers'=>true));
    $table->write();
    