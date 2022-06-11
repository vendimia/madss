<?php
namespace Vendimia\MadSS\Parser\Css;

use Vendimia\MadSS\Node;

/**
 * Convers a list of tokens to Nodes
 */
class Parser
{
    private Node $root;

    private $tokens;
    private $t_idx = 0;

    public function __construct(private Tokenizer $tokenizer)
    {
        $this->root = new Node;
        $this->tokens = $this->tokenizer->tokenize();
    }

    /**
     * Parses the token list until a BRACKET_CLOSE or end is reached.
     */
    public function parse_block($parent)
    {

        $pending_node = null;

        while ($token = ($this->tokens[$this->t_idx] ?? null)) {
            [$t_name, $t_value] = $token;

            switch ($t_name) {
                case Token::COMMENT:
                    $parent->addChild(new Node(
                        type: Node::T_COMMENT,
                        value: $t_value
                    ));
                    break;
                case Token::NAME:
                    // Creamos un nodo en el aire
                    $pending_node = $parent->addChild(new Node(
                        type: Node::T_NAME,
                        name: trim($t_value),
                    ));
                    break;
                case Token::AT_NAME:
                    // Creamos un nodo en el aire
                    $pending_node = $parent->addChild(new Node(
                        type: Node::T_AT_NAME,
                        name: trim($t_value),
                    ));
                    // NO HAY BREAK, por que después de un AT_NAME puede seguir
                    // un valor, lo mismo que si este token fuera un COLON
                case Token::COLON:
                case Token::BRACKET_CLOSE:

                    // Si es Token::COLON o Token::AT_NAME, el valor debe ser el
                    //      siguiente.
                    // Si es Token::BRACKET_CLOSE, el valor debe ser el
                    //      anterior.
                    if ($t_name == Token::COLON || $t_name == Token::AT_NAME) {
                        $position = 1;
                    } else {
                        $position = -1;
                    }

                    $token_value = $this->tokens[$this->t_idx + $position] ?? null;

                    // Si hay un nodo pendiente, el siguiente token debe ser
                    //  el valor
                    if ($pending_node && $token_value &&
                        ($token_value[0] == Token::VALUE)
                    ) {

                        $pending_node->setValue(trim($token_value[1]));

                        // Solo Los Token::NAME acaban aquí. Los AT_NAME pueden
                        // contener un bloque
                        if ($pending_node->getType() == Node::T_NAME) {
                            $pending_node = null;

                        }
                    }

                    // Un bracket close retorna el parseo de este bloque
                    if ($t_name == Token::BRACKET_CLOSE) {
                        return;
                    }
                    break;

                case Token::BRACKET_OPEN:
                    // Solo si hay un nodo pendiente
                    if ($pending_node) {
                        $this->t_idx++;
                        $this->parse_block($pending_node);
                        $pending_node = null;
                    }
                    break;
                case Token::BRACKET_CLOSE:
                    // Acabó el procesamiento de este bloque
                    return;
                }

            $this->t_idx++;
        }
    }

    /**
     * Parses the token list to Nodes
     */
    public function parse(): Node
    {
        $this->parse_block($this->root);

        return $this->root;
    }
}
