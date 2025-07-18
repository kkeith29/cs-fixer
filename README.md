# Code Style Fixer

Provides a standard configuration with some custom fixers implemented.

## Import Formatter Fixer

This fixer extracts all use statements from a namespace, reformats them according to PER-2 coding standard, and 
combines like use statements into groupings in an opinionated way while not exceeding max line length constraints.