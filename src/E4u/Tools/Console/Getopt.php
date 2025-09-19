<?php
namespace E4u\Tools\Console;

use E4u\Exception;

/**
 * Getopt is a class to parse options for command-line
 * applications.
 *
 * Terminology:
 * Argument: an element of the argv array.  This may be part of an option,
 *   or it may be a non-option command-line argument.
 * Flag: the letter or word set off by a '-' or '--'.  Example: in '--output filename',
 *   '--output' is the flag.
 * Parameter: the additional argument that is associated with the option.
 *   Example: in '--output filename', the 'filename' is the parameter.
 * Option: the combination of a flag and its parameter, if any.
 *   Example: in '--output filename', the whole thing is the option.
 *
 * The following features are supported:
 *
 * - Short flags like '-a'.  Short flags are preceded by a single
 *   dash.  Short flags may be clustered e.g. '-abc', which is the
 *   same as '-a' '-b' '-c'.
 * - Long flags like '--verbose'.  Long flags are preceded by a
 *   double dash.  Long flags may not be clustered.
 * - Options may have a parameter, e.g. '--output filename'.
 * - Parameters for long flags may also be set off with an equals sign,
 *   e.g. '--output=filename'.
 * - Parameters for long flags may be checked as string, word, or integer.
 * - Automatic generation of a helpful usage message.
 * - Signal end of options with '--'; subsequent arguments are treated
 *   as non-option arguments, even if they begin with '-'.
 * - Raise exception Laminas\Console\Exception\* in several cases
 *   when invalid flags or parameters are given.  Usage message is
 *   returned in the exception object.
 *
 * The format for specifying options uses a PHP associative array.
 * The key is has the format of a list of pipe-separated flag names,
 * followed by an optional '=' to indicate a required parameter or
 * '-' to indicate an optional parameter.  Following that, the type
 * of parameter may be specified as 's' for string, 'w' for word,
 * or 'i' for integer.
 *
 * Examples:
 * - 'user|username|u=s'  this means '--user' or '--username' or '-u'
 *   are synonyms, and the option requires a string parameter.
 * - 'p=i'  this means '-p' requires an integer parameter.  No synonyms.
 * - 'verbose|v-i'  this means '--verbose' or '-v' are synonyms, and
 *   they take an optional integer parameter.
 * - 'help|h'  this means '--help' or '-h' are synonyms, and
 *   they take no parameter.
 *
 * The values in the associative array are strings that are used as
 * brief descriptions of the options when printing a usage message.
 *
 * The simpler format for specifying options used by PHP's getopt()
 * function is also supported.  This is similar to GNU getopt and shell
 * getopt format.
 *
 * Example:  'abc:' means options '-a', '-b', and '-c'
 * are legal, and the latter requires a string parameter.
 */
class Getopt
{
    /**
     * The options for a given application can be in multiple formats.
     * modeGnu is for traditional 'ab:c:' style getopt format.
     * modeLaminas is for a more structured format.
     */
    const string
        MODE_ZEND    = 'laminas',
        MODE_LAMINAS = 'laminas',
        MODE_GNU     = 'gnu';

    /**
     * Constant tokens for various symbols used in the mode_laminas
     * rule format.
     */
    const string 
        PARAM_REQUIRED                    = '=',
        PARAM_OPTIONAL                    = '-',
        TYPE_STRING                       = 's',
        TYPE_WORD                         = 'w',
        TYPE_INTEGER                      = 'i',
        TYPE_NUMERIC_FLAG                 = '#';

    /**
     * These are constants for optional behavior of this class.
     * ruleMode is either 'laminas' or 'gnu' or a user-defined mode.
     * dashDash is true if '--' signifies the end of command-line options.
     * ignoreCase is true if '--opt' and '--OPT' are implicitly synonyms.
     * parseAll is true if all options on the command line should be parsed, regardless of
     * whether an argument appears before them.
     */
    const string
        CONFIG_RULEMODE                   = 'ruleMode',
        CONFIG_DASHDASH                   = 'dashDash',
        CONFIG_IGNORECASE                 = 'ignoreCase',
        CONFIG_PARSEALL                   = 'parseAll',
        CONFIG_CUMULATIVE_PARAMETERS      = 'cumulativeParameters',
        CONFIG_CUMULATIVE_FLAGS           = 'cumulativeFlags',
        CONFIG_PARAMETER_SEPARATOR        = 'parameterSeparator',
        CONFIG_FREEFORM_FLAGS             = 'freeformFlags',
        CONFIG_NUMERIC_FLAGS              = 'numericFlags';

    /**
     * Defaults for getopt configuration are:
     * ruleMode is 'laminas' format,
     * dashDash (--) token is enabled,
     * ignoreCase is not enabled,
     * parseAll is enabled,
     * cumulative parameters are disabled,
     * this means that subsequent options overwrite the parameter value,
     * cumulative flags are disable,
     * freeform flags are disable.
     */
    protected array $getoptConfig = [
        self::CONFIG_RULEMODE                => self::MODE_LAMINAS,
        self::CONFIG_DASHDASH                => true,
        self::CONFIG_IGNORECASE              => false,
        self::CONFIG_PARSEALL                => true,
        self::CONFIG_CUMULATIVE_PARAMETERS   => false,
        self::CONFIG_CUMULATIVE_FLAGS        => false,
        self::CONFIG_PARAMETER_SEPARATOR     => null,
        self::CONFIG_FREEFORM_FLAGS          => false,
        self::CONFIG_NUMERIC_FLAGS           => false
    ];

    /**
     * Stores the command-line arguments for the calling application.
     */
    protected array $argv = [];

    /**
     * Stores the name of the calling application.
     */
    protected string $progname = '';

    /**
     * Stores the list of legal options for this application.
     */
    protected array $rules = [];

    /**
     * Stores alternate spellings of legal options.
     */
    protected array $ruleMap = [];

    /**
     * Stores options given by the user in the current invocation
     * of the application, as well as parameters given in options.
     */
    protected array $options = [];

    /**
     * Stores the command-line arguments other than options.
     */
    protected array $remainingArgs = [];

    /**
     * State of the options: parsed or not yet parsed?
     */
    protected bool $parsed = false;

    /**
     * A list of callbacks to call when a particular option is present.
     */
    protected array $optionCallbacks = [];

    /**
     * The constructor takes one to three parameters.
     *
     * The first parameter is $rules, which may be a string for
     * gnu-style format, or a structured array for Laminas-style format.
     *
     * The second parameter is $argv, and it is optional.  If not
     * specified, $argv is inferred from the global argv.
     *
     * The third parameter is an array of configuration parameters
     * to control the behavior of this instance of Getopt; it is optional.
     */
    public function __construct(array $rules, ?array $argv = null, array $getoptConfig = [])
    {
        if (! isset($_SERVER['argv'])) {
            $errorDescription = !ini_get('register_argc_argv')
                ? "argv is not available, because ini option 'register_argc_argv' is set Off"
                : '$_SERVER["argv"] is not set, but Laminas\Console\Getopt cannot work without this information.';
            throw new Exception\ConfigException($errorDescription);
        }

        $this->progname = $_SERVER['argv'][0];
        $this->setOptions($getoptConfig);
        $this->addRules($rules);
        if (! is_array($argv)) {
            $argv = array_slice($_SERVER['argv'], 1);
        }
        if (isset($argv)) {
            $this->addArguments((array) $argv);
        }
    }

    /**
     * Return the state of the option seen on the command line of the
     * current application invocation.  This function returns true, or the
     * parameter to the option, if any.  If the option was not given,
     * this function returns null.
     *
     * The magic __get method works in the context of naming the option
     * as a virtual member of this class.
     */
    public function __get(string $key): string
    {
        return $this->getOption($key);
    }

    /**
     * Test whether a given option has been seen.
     */
    public function __isset(string $key): bool
    {
        $this->parse();
        if (isset($this->ruleMap[$key])) {
            $key = $this->ruleMap[$key];
            return isset($this->options[$key]);
        }
        return false;
    }

    /**
     * Set the value for a given option.
     */
    public function __set(string $key, string $value)
    {
        $this->parse();
        if (isset($this->ruleMap[$key])) {
            $key = $this->ruleMap[$key];
            $this->options[$key] = $value;
        }
    }

    /**
     * Return the current set of options and parameters seen as a string.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Unset an option.
     */
    public function __unset(string $key): void
    {
        $this->parse();
        if (isset($this->ruleMap[$key])) {
            $key = $this->ruleMap[$key];
            unset($this->options[$key]);
        }
    }

    /**
     * Define additional command-line arguments.
     * These are appended to those defined when the constructor was called.
     */
    public function addArguments(array $argv): static
    {
        $this->argv = array_merge($this->argv, $argv);
        $this->parsed = false;
        return $this;
    }

    /**
     * Define full set of command-line arguments.
     * These replace any currently defined.
     */
    public function setArguments(array $argv): static
    {
        $this->argv = $argv;
        $this->parsed = false;
        return $this;
    }

    /**
     * Define multiple configuration options from an associative array.
     * These are not program options, but properties to configure
     * the behavior of Laminas\Console\Getopt.
     */
    public function setOptions(array $getoptConfig): static
    {
        foreach ($getoptConfig as $key => $value) {
            $this->setOption($key, $value);
        }
        return $this;
    }

    /**
     * Define one configuration option as a key/value pair.
     * These are not program options, but properties to configure
     * the behavior of Laminas\Console\Getopt.
     */
    public function setOption(?string $configKey, string $configValue): static
    {
        if ($configKey !== null) {
            $this->getoptConfig[$configKey] = $configValue;
        }
        return $this;
    }

    /**
     * Define additional option rules.
     * These are appended to the rules defined when the constructor was called.
     */
    public function addRules(array $rules): static
    {
        $ruleMode = $this->getoptConfig['ruleMode'];
        switch ($this->getoptConfig['ruleMode']) {
            case self::MODE_LAMINAS:
                $this->_addRulesModeLaminas($rules);
                break;
            default:
                /**
                 * Call addRulesModeFoo() for ruleMode 'foo'.
                 * The developer should subclass Getopt and
                 * provide this method.
                 */
                $method = '_addRulesMode' . ucfirst($ruleMode);
                $this->$method($rules);
        }
        $this->parsed = false;
        return $this;
    }

    /**
     * Return the current set of options and parameters seen as a string.
     */
    public function toString(): string
    {
        $this->parse();
        $s = [];
        foreach ($this->options as $flag => $value) {
            $s[] = $flag . '=' . ($value === true ? 'true' : $value);
        }
        return implode(' ', $s);
    }

    /**
     * Return the current set of options and parameters seen
     * as an array of canonical options and parameters.
     *
     * Clusters have been expanded, and option aliases
     * have been mapped to their primary option names.
     */
    public function toArray(): array
    {
        $this->parse();
        $s = [];
        foreach ($this->options as $flag => $value) {
            $s[] = $flag;
            if ($value !== true) {
                $s[] = $value;
            }
        }
        return $s;
    }

    /**
     * Return the current set of options and parameters seen in Json format.
     */
    public function toJson(): string
    {
        $this->parse();
        $j = [];
        foreach ($this->options as $flag => $value) {
            $j['options'][] = [
                'option' => [
                    'flag' => $flag,
                    'parameter' => $value
                ]
            ];
        }

        return json_encode($j);
    }

    public function getOptions(): array
    {
        $this->parse();
        return array_keys($this->options);
    }

    /**
     * Return the state of the option seen on the command line of the
     * current application invocation.
     *
     * This function returns true, or the parameter value to the option, if any.
     * If the option was not given, this function returns false.
     */
    public function getOption(string $flag): mixed
    {
        $this->parse();
        if ($this->getoptConfig[self::CONFIG_IGNORECASE]) {
            $flag = strtolower($flag);
        }
        if (isset($this->ruleMap[$flag])) {
            $flag = $this->ruleMap[$flag];
            if (isset($this->options[$flag])) {
                return $this->options[$flag];
            }
        }
        
        return null;
    }

    /**
     * Return the arguments from the command-line following all options found.
     */
    public function getRemainingArgs(): array
    {
        $this->parse();
        return $this->remainingArgs;
    }

    public function getArguments(): array
    {
        $result = $this->getRemainingArgs();
        foreach ($this->getOptions() as $option) {
            $result[$option] = $this->getOption($option);
        }
        return $result;
    }

    /**
     * Return a useful option reference, formatted for display in an
     * error message.
     *
     * Note that this usage information is provided in most Exceptions
     * generated by this class.
     */
    public function getUsageMessage(): string
    {
        $usage = "Usage: {$this->progname} [ options ]\n";
        $maxLen = 20;
        $lines = [];
        foreach ($this->rules as $rule) {
            if (isset($rule['isFreeformFlag'])) {
                continue;
            }
            $flags = [];
            if (is_array($rule['alias'])) {
                foreach ($rule['alias'] as $flag) {
                    $flags[] = (strlen($flag) == 1 ? '-' : '--') . $flag;
                }
            }
            $linepart['name'] = implode('|', $flags);
            if (isset($rule['param']) && $rule['param'] != 'none') {
                $linepart['name'] .= ' ';
                switch ($rule['param']) {
                    case 'optional':
                        $linepart['name'] .= "[ <{$rule['paramType']}> ]";
                        break;
                    case 'required':
                        $linepart['name'] .= "<{$rule['paramType']}>";
                        break;
                }
            }
            if (strlen($linepart['name']) > $maxLen) {
                $maxLen = strlen($linepart['name']);
            }
            $linepart['help'] = '';
            if (isset($rule['help'])) {
                $linepart['help'] .= $rule['help'];
            }
            $lines[] = $linepart;
        }
        foreach ($lines as $linepart) {
            $usage .= sprintf(
                "%s %s\n",
                str_pad($linepart['name'], $maxLen),
                $linepart['help']
            );
        }
        return $usage;
    }

    /**
     * Define aliases for options.
     *
     * The parameter $aliasMap is an associative array
     * mapping option name (short or long) to an alias.
     */
    public function setAliases(array $aliasMap): static
    {
        foreach ($aliasMap as $flag => $alias) {
            if ($this->getoptConfig[self::CONFIG_IGNORECASE]) {
                $flag = strtolower($flag);
                $alias = strtolower($alias);
            }
            if (! isset($this->ruleMap[$flag])) {
                continue;
            }
            $flag = $this->ruleMap[$flag];
            if (isset($this->rules[$alias]) || isset($this->ruleMap[$alias])) {
                $o = (strlen($alias) == 1 ? '-' : '--') . $alias;
                throw new Exception\ConfigException("Option \"$o\" is being defined more than once.");
            }
            $this->rules[$flag]['alias'][] = $alias;
            $this->ruleMap[$alias] = $flag;
        }
        return $this;
    }

    /**
     * Define help messages for options.
     *
     * The parameter $helpMap is an associative array
     * mapping option name (short or long) to the help string.
     */
    public function setHelp(array $helpMap): static
    {
        foreach ($helpMap as $flag => $help) {
            if (! isset($this->ruleMap[$flag])) {
                continue;
            }
            $flag = $this->ruleMap[$flag];
            $this->rules[$flag]['help'] = $help;
        }
        return $this;
    }

    /**
     * Parse command-line arguments and find both long and short
     * options.
     *
     * Also find option parameters, and remaining arguments after
     * all options have been parsed.
     */
    public function parse(): static
    {
        if ($this->parsed === true) {
            return $this;
        }

        $argv = $this->argv;
        $this->options = [];
        $this->remainingArgs = [];
        while (count($argv) > 0) {
            if ($argv[0] == '--') {
                array_shift($argv);
                if ($this->getoptConfig[self::CONFIG_DASHDASH]) {
                    $this->remainingArgs = array_merge($this->remainingArgs, $argv);
                    break;
                }
            }
            if (str_starts_with($argv[0], '--')) {
                $this->_parseLongOption($argv);
            } elseif (str_starts_with($argv[0], '-') && ('-' != $argv[0] || count($argv) > 1)) {
                $this->_parseShortOptionCluster($argv);
            } elseif ($this->getoptConfig[self::CONFIG_PARSEALL]) {
                $this->remainingArgs[] = array_shift($argv);
            } else {
                /*
                 * We should put all other arguments in remainingArgs and stop parsing
                 * since CONFIG_PARSEALL is false.
                 */
                $this->remainingArgs = array_merge($this->remainingArgs, $argv);
                break;
            }
        }
        $this->parsed = true;

        //go through parsed args and process callbacks
        $this->triggerCallbacks();

        return $this;
    }

    /**
     * $option   The name of the property which, if present, will call the passed
     *           callback with the value of this parameter.
     * $callback The callback that will be called for this option. The first
     *           parameter will be the value of getOption($option), the second
     *           parameter will be a reference to $this object. If the callback returns
     *           false then an Exception\RuntimeException will be thrown indicating that
     *           there is a parse issue with this option.
     */
    public function setOptionCallback(string $option, \Closure $callback): static
    {
        $this->optionCallbacks[$option] = $callback;

        return $this;
    }

    /**
     * Triggers all the registered callbacks.
     */
    protected function triggerCallbacks(): void
    {
        foreach ($this->optionCallbacks as $option => $callback) {
            if (null === $this->getOption($option)) {
                continue;
            }
            //make sure we've resolved the alias, if using one
            if (isset($this->ruleMap[$option]) && $option = $this->ruleMap[$option]) {
                if (false === $callback($this->getOption($option), $this)) {
                    throw new Exception\RuntimeException(
                        "The option $option is invalid. See usage.",
                        $this->getUsageMessage()
                    );
                }
            }
        }
    }

    /**
     * Parse command-line arguments for a single long option.
     * A long option is preceded by a double '--' character.
     * Long options may not be clustered.
     */
    // @codingStandardsIgnoreStart
    protected function _parseLongOption(mixed &$argv): void
    {
        // @codingStandardsIgnoreEnd
        $optionWithParam = ltrim(array_shift($argv), '-');
        $l = explode('=', $optionWithParam, 2);
        $flag = array_shift($l);
        $param = array_shift($l);
        if (isset($param)) {
            array_unshift($argv, $param);
        }
        $this->_parseSingleOption($flag, $argv);
    }

    /**
     * Parse command-line arguments for short options.
     * Short options are those preceded by a single '-' character.
     * Short options may be clustered.
     */
    // @codingStandardsIgnoreStart
    protected function _parseShortOptionCluster(mixed &$argv): void
    {
        // @codingStandardsIgnoreEnd
        $flagCluster = ltrim(array_shift($argv), '-');
        foreach (str_split($flagCluster) as $flag) {
            $this->_parseSingleOption($flag, $argv);
        }
    }

    /**
     * Parse command-line arguments for a single option.
     */
    // @codingStandardsIgnoreStart
    protected function _parseSingleOption(string $flag, mixed &$argv): void
    {
        // @codingStandardsIgnoreEnd
        if ($this->getoptConfig[self::CONFIG_IGNORECASE]) {
            $flag = strtolower($flag);
        }

        // Check if this option is numeric one
        if (preg_match('/^\d+$/', $flag)) {
            $this->_setNumericOptionValue($flag);
            return;
        }

        if (! isset($this->ruleMap[$flag])) {
            // Don't throw Exception for flag-like param in case when freeform flags are allowed
            if (! $this->getoptConfig[self::CONFIG_FREEFORM_FLAGS]) {
                throw new Exception\RuntimeException(
                    "Option \"$flag\" is not recognized.",
                    $this->getUsageMessage()
                );
            }

            // Magic methods in future will use this mark as real flag value
            $this->ruleMap[$flag] = $flag;
            $realFlag = $flag;
            $this->rules[$realFlag] = [
                'param'          => 'optional',
                'isFreeformFlag' => true
            ];
        } else {
            $realFlag = $this->ruleMap[$flag];
        }

        switch ($this->rules[$realFlag]['param']) {
            case 'required':
                if (count($argv) > 0) {
                    $param = array_shift($argv);
                    $this->_checkParameterType($realFlag, $param);
                } else {
                    throw new Exception\RuntimeException(
                        "Option \"$flag\" requires a parameter.",
                        $this->getUsageMessage()
                    );
                }
                break;
            case 'optional':
                if (count($argv) > 0 && !str_starts_with($argv[0], '-')) {
                    $param = array_shift($argv);
                    $this->_checkParameterType($realFlag, $param);
                } else {
                    $param = true;
                }
                break;
            default:
                $param = true;
        }

        $this->_setSingleOptionValue($realFlag, $param);
    }

    /**
     * Set given value as value of numeric option
     *
     * Throw runtime exception if this action is deny by configuration
     * or no one numeric option handlers is defined
     */
    // @codingStandardsIgnoreStart
    protected function _setNumericOptionValue(int $value): void
    {
        // @codingStandardsIgnoreEnd
        if (! $this->getoptConfig[self::CONFIG_NUMERIC_FLAGS]) {
            throw new Exception\RuntimeException("Using of numeric flags are deny by configuration");
        }

        if (empty($this->getoptConfig['numericFlagsOption'])) {
            throw new Exception\RuntimeException("Any option for handling numeric flags are specified");
        }

        $this->_setSingleOptionValue($this->getoptConfig['numericFlagsOption'], $value);
    }

    /**
     * Add relative to options' flag value
     *
     * If options list already has current flag as key
     * and parser should follow cumulative params by configuration,
     * we should to add new param to array, not to overwrite
     */
    // @codingStandardsIgnoreStart
    protected function _setSingleOptionValue(string $flag, string|bool $value): void
    {
        // @codingStandardsIgnoreEnd
        if (true === $value && $this->getoptConfig[self::CONFIG_CUMULATIVE_FLAGS]) {
            // For boolean values we have to create new flag, or increase number of flags' usage count
            $this->_setBooleanFlagValue($flag);
            return;
        }

        // Split multiple values, if necessary
        // Filter empty values from splited array
        $separator = $this->getoptConfig[self::CONFIG_PARAMETER_SEPARATOR];
        if (is_string($value) && ! empty($separator) && is_string($separator) && substr_count($value, $separator)) {
            $value = array_filter(explode($separator, $value));
        }

        if (! array_key_exists($flag, $this->options)) {
            $this->options[$flag] = $value;
        } elseif ($this->getoptConfig[self::CONFIG_CUMULATIVE_PARAMETERS]) {
            $this->options[$flag] = (array) $this->options[$flag];
            $this->options[$flag][] = $value;
        } else {
            $this->options[$flag] = $value;
        }
    }

    /**
     * Set TRUE value to given flag, if this option does not exist yet
     * In other case increase value to show count of flags' usage
     */
    // @codingStandardsIgnoreStart
    protected function _setBooleanFlagValue(string $flag): void
    {
        // @codingStandardsIgnoreEnd
        $this->options[$flag] = array_key_exists($flag, $this->options)
            ? (int) $this->options[$flag] + 1
            : true;
    }

    /**
     * Return true if the parameter is in a valid format for
     * the option $flag.
     * Throw an exception in most other cases.
     */
    // @codingStandardsIgnoreStart
    protected function _checkParameterType(string $flag, string $param): bool
    {
        // @codingStandardsIgnoreEnd
        $type = 'string';
        if (isset($this->rules[$flag]['paramType'])) {
            $type = $this->rules[$flag]['paramType'];
        }
        switch ($type) {
            case 'word':
                if (preg_match('/\W/', $param)) {
                    throw new Exception\RuntimeException(
                        "Option \"$flag\" requires a single-word parameter, but was given \"$param\".",
                        $this->getUsageMessage()
                    );
                }
                break;
            case 'integer':
                if (preg_match('/\D/', $param)) {
                    throw new Exception\RuntimeException(
                        "Option \"$flag\" requires an integer parameter, but was given \"$param\".",
                        $this->getUsageMessage()
                    );
                }
                break;
            case 'string':
            default:
                break;
        }
        return true;
    }
    
    /**
     * Define legal options using the Laminas-style format.
     */
    // @codingStandardsIgnoreStart
    protected function _addRulesModeLaminas(array $rules): void
    {
        // @codingStandardsIgnoreEnd
        foreach ($rules as $ruleCode => $helpMessage) {
            // this may have to translate the long parm type if there
            // are any complaints that =string will not work (even though that use
            // case is not documented)
            if (in_array(substr($ruleCode, -2, 1), ['-', '='])) {
                $flagList  = substr($ruleCode, 0, -2);
                $delimiter = substr($ruleCode, -2, 1);
                $paramType = substr($ruleCode, -1);
            } else {
                $flagList = $ruleCode;
                $delimiter = $paramType = null;
            }
            if ($this->getoptConfig[self::CONFIG_IGNORECASE]) {
                $flagList = strtolower($flagList);
            }
            $flags = explode('|', $flagList);
            $rule = [];
            $mainFlag = $flags[0];
            foreach ($flags as $flag) {
                if (empty($flag)) {
                    throw new Exception\ConfigException("Blank flag not allowed in rule \"$ruleCode\".");
                }
                if (strlen($flag) == 1) {
                    if (isset($this->ruleMap[$flag])) {
                        throw new Exception\ConfigException(
                            "Option \"-$flag\" is being defined more than once."
                        );
                    }
                    $this->ruleMap[$flag] = $mainFlag;
                    $rule['alias'][] = $flag;
                } else {
                    if (isset($this->rules[$flag]) || isset($this->ruleMap[$flag])) {
                        throw new Exception\ConfigException(
                            "Option \"--$flag\" is being defined more than once."
                        );
                    }
                    $this->ruleMap[$flag] = $mainFlag;
                    $rule['alias'][] = $flag;
                }
            }
            if (isset($delimiter)) {
                $rule['param'] = match ($delimiter) {
                    self::PARAM_REQUIRED => 'required',
                    default => 'optional',
                };
                switch (substr($paramType, 0, 1)) {
                    case self::TYPE_WORD:
                        $rule['paramType'] = 'word';
                        break;
                    case self::TYPE_INTEGER:
                        $rule['paramType'] = 'integer';
                        break;
                    case self::TYPE_NUMERIC_FLAG:
                        $rule['paramType'] = 'numericFlag';
                        $this->getoptConfig['numericFlagsOption'] = $mainFlag;
                        break;
                    case self::TYPE_STRING:
                    default:
                        $rule['paramType'] = 'string';
                }
            } else {
                $rule['param'] = 'none';
            }
            $rule['help'] = $helpMessage;
            $this->rules[$mainFlag] = $rule;
        }
    }
}
