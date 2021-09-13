<?php
namespace Vendimia\MadSS\Parser\Css;

class Tokenizer
{
    const SPACE = " \n\r";

    private $ptr = 0;
    public $tokens = [];

    public function __construct(
        private string $source = ''
    )
    {
    }

    /** 
     * Sets a new source file. Resets all the values
     */
    public function setSource($source)
    {
        $this->source = $source;
        $this->tokens = [];
        $this->ptr = 0;
    }

    /**
     * Converts the CSS source to an array of tokens
     */
    public function tokenize(): array
    {
        if ($this->tokens) {
            return $this->tokens;
        }

        $stage = 'waiting';
        $prev_stage = null;
        $next_stage = 'identifier';

        $start_ptr = $this->ptr;

        while(!is_null($char = $this->source[$this->ptr++] ?? null)) {

            // Si estamos en comentario, no hacemos nada hasta acabarlo
            if ($stage == 'comment') {
                if ($char == '*' && ($this->source[$this->ptr] ?? null) == '/') {
                    $value = trim(substr(
                        $this->source, 
                        $start_ptr, 
                        $this->ptr - $start_ptr - 1
                    ));

                    // FIXME: Por ahora, no añadimos el comentartio como token
                    //$this->tokens[] = [Token::COMMENT, $value];
                                        
                    //$next_stage = $prev_stage;
                    $stage = $next_stage;

                    $start_ptr = ++$this->ptr;
                }
                continue;
            }


            // Si el caracter es un espacio:
            if (str_contains(self::SPACE, $char)) {
                // Si estamos esperando, seguimos esperando
                if ($stage == 'waiting') {
                    $start_ptr = $this->ptr;
                    continue;
                }
            } else {
                // Si no es un espacio, y estamos esperando, entonces empezamos
                // a procesar
                if ($stage == 'waiting') {
                    $stage = $next_stage;
                }
            }

            // Estos dos caracteres acaban un identificador
            if ($stage == 'identifier') {
                // Empezamos un comentario?
                if ($char == '/' && ($this->source[$this->ptr] ?? null) == '*') {
                    $next_stage = $stage;
                    $stage = 'comment';
                    $start_ptr = ++$this->ptr;
                    continue;
                }

                if ($char == ';' || $char == '{' || $char == '}') {
                    $identifier_closing_token = match ($char) {
                        ';' => Token::SEMI_COLON,
                        '{' => Token::BRACKET_OPEN,
                        '}' => Token::BRACKET_CLOSE,
                    };

                    $identifier = trim(substr(
                        $this->source, 
                        $start_ptr, 
                        $this->ptr - $start_ptr - 1
                    ));

                    // Si no hay identifier, guardamos el token, y continuamos
                    if (!$identifier) {
                        $this->tokens[] = [$identifier_closing_token, $char];
                        $start_ptr = $this->ptr;
                        //$this->ptr++;

                        $stage  = 'waiting';
                        $next_stage = 'identifier';
   
                        continue;
                    }

                    $divisor = null;
                    $divisor_token = null;
                    if ($identifier[0] == '@') {
                        $token = Token::AT_NAME;
                        $divisor = ' ';
                    } else {
                        $token = Token::NAME; 
                        // Solo hay valor si el identificador de cierre es ';' o '}'
                        if ($char == ';' || $char == '}') {
                            $divisor = ':';
                            $divisor_token = Token::COLON;
                        }
                    }
    
                    if ($divisor && str_contains($identifier, $divisor)) {
                        [$name, $value] = explode($divisor, $identifier, 2);
                    } else {
                        $name = $identifier;
                        $value = null;
                    }
    
    
                    // Añadimos los tokens
                    $this->tokens[] = [$token, $name];
                    if ($divisor_token) {
                        $this->tokens[] = [$divisor_token, $divisor];
                    }
                    if ($value) {
                        $this->tokens[] = [Token::VALUE, $value];
                    }

                    $this->tokens[] = [$identifier_closing_token, $char];
    
                    // Esperamos que empiece un caracter
                    $stage  = 'waiting';
                    $next_stage = 'identifier';
    
                    $start_ptr = $this->ptr;
                } 
            }

        }
        return $this->tokens;
    }
}