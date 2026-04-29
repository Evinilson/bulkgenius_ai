# [Roadmap] Evolução do Motor de Conteúdos AI

Este documento detalha a transição de um sistema de prompt único para uma biblioteca de conteúdos inteligente e eficiente no módulo BulkGenius AI.

## Fase 1: Desacoplamento e Arquitetura (O Alicerce)
O objetivo é isolar a lógica dos prompts para facilitar a manutenção e evolução.
- [x] **Criação da Classe `PromptManager`**: Centralizar todos os templates de texto.
- [x] **Sistema de Placeholders**: Implementar suporte para variáveis dinâmicas (ex: `{{product_name}}`, `{{current_content}}`).
- [x] **Seleção de Contexto**: Interface para definir se a intenção é "Criar" ou "Otimizar".

## Fase 2: Especialização de Campos (Otimização de Tokens)
Implementar prompts específicos e formatos de dados ultra-eficientes.
- [x] **Prompt de Resumo (Short Description)**: Focado em estrutura HTML `<h2>` e parágrafos curtos.
- [x] **Prompt de Descrição (Longa)**: Focado em benefícios e especificações técnicas.
- [x] **Prompt de SEO**: Focado estritamente em limites de caracteres.
- [ ] **Transição para TOON (Token-Oriented Object Notation)**: Substituir JSON por TOON nas respostas da IA para reduzir o consumo de tokens em 30-60%.
- [ ] **Parser PHP para TOON**: Desenvolver lógica personalizada para converter a resposta da IA em dados PrestaShop sem a redundância do JSON.

## Fase 3: Personalização de Tom e Estilo (UX Avançada)
Dar o controlo da "personalidade" da marca ao utilizador.
- [ ] **Biblioteca de Tonalidades**: *Profissional, Persuasivo, Criativo, Técnico, Amigável*.
- [ ] **Interface de Utilizador (UI)**: Adicionar seletor de tom no modal de otimização.
- [ ] **Formatos de Saída**: Opções para Bullet Points, Tabelas ou Parágrafos.

## Fase 4: Inteligência de Contexto e Idiomas
- [ ] **Contexto Cruzado**: Usar a descrição longa como base para gerar a curta (e vice-versa) para evitar contradições.
- [x] **Nuances Regionais**: Prompts otimizados para PT-PT vs PT-BR e EN-UK vs EN-US.
- [x] **Instruções em Inglês (Token Efficiency)**: Escrever as instruções do prompt em Inglês para reduzir o consumo de tokens de entrada, mantendo o output na língua do utilizador.

---

> [!NOTE]
> Este roadmap é um documento vivo e deve ser atualizado conforme novas necessidades surjam durante o desenvolvimento.
