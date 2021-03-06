# Upnid for Woocommerce

Receba pagamentos por cartão de crédito e boleto bancário utilizando a Upnid. Vender pela Internet nunca foi tão simples!

## Descrição

A [Upnid](https://upnid.com/) é a solução mais eficiente para você receber pagamentos pela Internet por cartão de crédito e boleto bancário, sem que seu cliente saia da sua loja Woocommerce.

### Compatibilidade

Compatível desde a versão 2.2.x do WooCommerce.

Este plugin funciona integrado com o [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/), desta forma é possível enviar documentos do cliente como "CPF" ou "CNPJ", além dos campos "número" e "bairro" do endereço. Caso você queira remover todos os campos adicionais de endereço para vender Digital Goods, é possível utilizar o plugin [WooCommerce Digital Goods Checkout](https://wordpress.org/plugins/wc-digital-goods-checkout/).

### Instalação

Confira o nosso guia de instalação e configuração do Upnid na aba [Installation](http://wordpress.org/plugins/upnid-woocommerce/installation/).

### Dúvidas?

Você pode esclarecer suas dúvidas usando:

* A nossa sessão de [FAQ](http://wordpress.org/plugins/upnid-woocommerce/faq/).
* Criando um tópico no [fórum de ajuda do WordPress](http://wordpress.org/support/plugin/upnid-woocommerce).
* Criando um tópico no [fórum do Github](https://github.com/upnid/upnid-woocommerce/issues).

### Colaborar

Você pode contribuir com código-fonte em nossa página no [GitHub](https://github.com/upnid/upnid-woocommerce).

### Como instalar?

#### Instalação do plugin:

* Envie os arquivos do plugin para a pasta wp-content/plugins, ou instale usando o instalador de plugins do WordPress.
* Ative o plugin.

### Requerimentos:

É necessário possuir uma conta na [Upnid](https://upnid.com/) e ter instalado o [WooCommerce](http://wordpress.org/plugins/woocommerce/).

### Configurações do Plugin:

Com o plugin instalado acesse o admin do WordPress e entre em "WooCommerce" > "Configurações" > "Pagamentos" e configure as opção "Upnid - Boleto bancário" e "Upnid - Cartão de crédito".

Habilite a opção que você deseja, preencha o campo **Chave de API** com a chave que você criar dentro da sua conta na Upnid em **Desenvolvedor**/**API Keys**.

Essa API Key deve ter permissão para **Criar, visualizar, alterar e excluir** Produtos, Pagamentos e Webhooks.

Também será necessário utilizar o plugin [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/) para poder enviar campos de CPF e CNPJ.

Pronto, sua loja já pode receber pagamentos pela Upnid.

### FAQ

#### Qual é a licença do plugin?

Este plugin esta licenciado como GPL.

#### O que eu preciso para utilizar este plugin?

* Ter instalado o plugin WooCommerce 2.2 ou superior.
* Possuir uma conta no [Upnid](https://upnid.com/).
* Criar uma **Chave de API** na Upnid, dando permissão para **Criar, visualizar, alterar e excluir** Produtos, Pagamentos e Webhooks.

#### Quanto custa para usar a Upnid?

Confira os preços em "[Upnid](https://upnid.com/)".

#### O pedido foi pago e ficou com o status de "processando" e não como "concluído", isto está certo?

Sim, esta certo e significa que o plugin esta trabalhando como deveria.

Todo gateway de pagamentos no WooCommerce deve mudar o status do pedido para "processando" no momento que é confirmado o pagamento e nunca deve ser alterado sozinho para "concluído", pois o pedido deve ir apenas para o status "concluído" após ele ter sido entregue.

Para produtos baixáveis a configuração padrão do WooCommerce é permitir o acesso apenas quando o pedido tem o status "concluído", entretanto nas configurações do WooCommerce na aba *Produtos* é possível ativar a opção **"Conceder acesso para download do produto após o pagamento"** e assim liberar o download quando o status do pedido esta como "processando".

#### É obrigatório enviar todos os campos para processar o pagamento?

Não é obrigatório caso você venda digital goods.

É possível remover os campos de endereço, empresa e telefone, mantendo apenas nome, sobrenome e e-mail utilizando o plugin [WooCommerce Digital Goods Checkout](https://wordpress.org/plugins/wc-digital-goods-checkout/).

#### Problemas com a integração?

Primeiro de tudo ative a opção **Log de depuração** e tente realizar o pagamento novamente.

Feito isso copie o conteúdo do log e salve usando o [pastebin.com](http://pastebin.com) ou o [gist.github.com](http://gist.github.com), depois basta abrir um tópico de suporte [aqui](http://wordpress.org/support/plugin/upnid-woocommerce).

#### Mais dúvidas relacionadas ao funcionamento do plugin?

Entre em contato [clicando aqui](http://wordpress.org/support/plugin/upnid-woocommerce).