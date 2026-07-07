<template>
  <LegalDocument title="Termos de Uso" updated-at="6 de julho de 2026">
    <p>
      Bem-vindo(a) ao <strong>AcadFlow</strong>. Estes Termos de Uso regulam o acesso e a
      utilização da nossa plataforma de organização acadêmica. Ao criar uma conta ou utilizar
      o serviço, você concorda integralmente com as condições descritas abaixo.
    </p>

    <h2>1. Aceitação</h2>
    <p>
      Ao se cadastrar, você declara ter lido e aceito estes Termos e a nossa
      <RouterLink to="/privacidade">Política de Privacidade</RouterLink>. Caso não concorde,
      não utilize a plataforma.
    </p>

    <h2>2. Elegibilidade e conta</h2>
    <ul>
      <li>Você é responsável por manter a confidencialidade das suas credenciais de acesso.</li>
      <li>As informações fornecidas no cadastro devem ser verdadeiras e mantidas atualizadas.</li>
      <li>É necessário confirmar seu e-mail para liberar o uso completo dos recursos.</li>
    </ul>

    <h2>3. Uso aceitável</h2>
    <p>Você concorda em não utilizar o AcadFlow para:</p>
    <ul>
      <li>Violar leis, direitos de terceiros ou propriedade intelectual;</li>
      <li>Enviar conteúdo ilícito, ofensivo, fraudulento ou malicioso;</li>
      <li>Comprometer a segurança, a integridade ou o desempenho do serviço.</li>
    </ul>

    <h2>4. Conteúdo do usuário</h2>
    <p>
      Você mantém a titularidade dos projetos, tarefas, arquivos e demais conteúdos que criar.
      Concede ao AcadFlow apenas a licença necessária para armazenar e processar esses dados
      com o objetivo de operar a plataforma.
    </p>

    <h2>5. Planos e assinaturas</h2>
    <p>
      Recursos pagos podem estar sujeitos a assinatura. Valores, ciclos de cobrança e condições
      de cancelamento são apresentados no momento da contratação. Cancelamentos interrompem
      renovações futuras, sem reembolso proporcional, salvo disposição legal em contrário.
    </p>

    <h2>6. Disponibilidade</h2>
    <p>
      Empenhamo-nos em manter o serviço disponível e seguro, mas ele é fornecido "como está".
      Podemos realizar manutenções, atualizações e melhorias que eventualmente afetem a
      disponibilidade temporária.
    </p>

    <h2>7. Encerramento</h2>
    <p>
      Você pode encerrar sua conta a qualquer momento nas configurações. Podemos suspender ou
      encerrar contas que violem estes Termos, resguardados os seus direitos previstos em lei.
    </p>

    <h2>8. Alterações</h2>
    <p>
      Estes Termos podem ser atualizados periodicamente. Mudanças relevantes serão comunicadas
      pelos canais oficiais. O uso continuado após alterações implica concordância com a nova versão.
    </p>

    <h2>9. Contato</h2>
    <p>
      Dúvidas sobre estes Termos podem ser enviadas para
      <a href="mailto:contato@acadflow.com">contato@acadflow.com</a>.
    </p>
  </LegalDocument>
</template>

<script setup lang="ts">
import LegalDocument from '@/components/legal/LegalDocument.vue'
</script>
