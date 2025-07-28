# Informações Acerca do Projeto:

## Sistema PPC - Plano Pedagógico do Curso

   - O **Protótipo de Sistema WEB** é um projeto desenvolvido como parte do processo seletivo para estágio na Assessoria Técnica de Tecnologia da Informação e Comunicação (ASTTIC/PROEG) da Universidade Federal do Pará e tem como objetivo permitir:

## Submissão de propostas de cursos por unidades acadêmicas, contendo:
  
  - Nome do curso
  - Carga horária total
  - Quantidade de semestres
  - Justificativa da criação do curso
  - Impacto social
  - Lista de disciplinas, distribuídas por semestre, com nomes e cargas horárias

## Avaliação da proposta por um servidor designado (avaliador), que poderá:

   - Inserir comentários e recomendações
   - Enviar a proposta de volta à unidade
   - Encaminhá-la à Câmara de Ensino

## Decisão final da Câmara de Ensino, que poderá:

   - Aprovar ou reprovar a proposta

## Tecnologias Utilizadas:

- **Laravel** (PHP) – Backend e API
- **Vue.js** – Frontend
- **MySQL** – Banco de dados
- **Git/GitHub** – Controle de versão

## Guia para Execução do Projeto:

# Pré-requisitos

- Antes de começar, certifique-se de ter o seguinte instalado no seu computador:

- Node.js (versão 18 ou superior) e npm (gerenciador de pacotes do Node.js).

- PHP (versão 8.1 ou superior) e Composer (gerenciador de dependências PHP).

- Um servidor de banco de dados MySQL em execução (por exemplo, via XAMPP, WAMP, Docker ou instalação direta).

# Configuração do Backend (Laravel API)

- Clone o Repositório do Backend:

```

   git clone https://github.com/Gabriel8Kbps/Desafio-Pratico-ASTTIC.git
   cd sistema_ppc # Navegue para a pasta clonada

```

- Instale as Dependências do Laravel:

```

   composer install

```

- Configure o Ambiente:

```

   cp .env.example .env

```

Abra o arquivo .env e configure as seguintes linhas com seus dados:

```

DB_DATABASE=seu_nome_de_banco
DB_USERNAME=seu_usuario_mysql
DB_PASSWORD=sua_senha_mysql

```

- Gere a Chave da Aplicação:

```
php artisan key:generate
```

- Execute as Migrações e Semeie o Banco:

```
php artisan migrate
php artisan db:seed # Caso queira adicionar dados de teste
```

- Inicie o Servidor Laravel:

```
php artisan serve
```
Mantenha este terminal aberto enquanto usa o sistema.

# Configuração do Frontend (Vue.js)

- Mude para a pasta de aplicação

```
cd sistema_ppc_frontend
npm install
```

- Inicie o Servidor de Desenvolvimento Vue.js:

```
npm run dev
```

# Acessando o Sistema

- Com ambos os servidores (backend e frontend) rodando, abra o navegador e acesse o endereço frontend:

```

http://localhost:5173

```




Autor: Gabriel J. S. Costa
