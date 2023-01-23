<template>
  <div>
    <Head title="Two factor authentication (2FA)" />

    <form
      @submit.prevent="verify"
      class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 max-w-[25rem] mx-auto"
    >
      <h2 class="text-2xl text-center font-normal mb-6 text-90">
        {{ __('2-step verification') }}
      </h2>

      <DividerLine />

      <p class="font-normal text-center mb-6 text-90">
        {{ __('Add an extra layer of security to your account by using a one-time security code in addition to your password each time you log in.')}}
      </p>

      <p v-if="recoveryEnabled" class="font-normal text-center mb-6 text-90">
        {{ __('Store the recovery codes below in a safe space.')}}
        <br>
        <code class="mt-2">
          <span v-for="recoveryCode in recoveryCodes">{{ recoveryCode}}<br></span>
        </code>
      </p>

      <p class="font-normal text-center mb-6 text-90">
        {{ __('Scan the QR code below or manually type the secret key into your authenticator app.') }}
        <img :src="qrCodeUrl" :alt="secretKey" class="mx-auto">
        <span class="text-xxs">{{ secretKey }}</span>
      </p>

      <div class="mb-6">
        <label class="block mb-2" for="code">
          {{ __('Enter the 6-digit code you see in your authenticator app.') }}
        </label>
        <input
          v-model="form.code"
          class="form-control form-input form-input-bordered w-full"
          :class="{ 'form-input-border-error': form.errors.has('code') }"
          id="code"
          type="text"
          name="code"
          autofocus=""
          required
        />

        <HelpText class="mt-2 text-red-500" v-if="form.errors.has('code')">
          {{ form.errors.first('code') }}
        </HelpText>
      </div>

      <DefaultButton class="w-full flex justify-center mb-6" type="submit">
        <span>
          {{ __('Verify') }}
        </span>
      </DefaultButton>

    </form>
  </div>
</template>

<script>
import Auth from '../../../vendor/laravel/nova/resources/js/layouts/Auth'

export default {

  layout: Auth,

  props: ['recoveryEnabled', 'recoveryCodes', 'secretKey', 'qrCodeUrl'],

  data: () => ({
    form: Nova.form({
      code: ''
    })
  }),

  mounted() {
    //
  },

  methods: {
    async verify() {
      try {
        const { redirect } = await this.form.post('/nova-vendor/nova-2fa/confirm')

        if (redirect !== undefined && redirect !== null) {
          let path = { url: redirect, remote: true }
          Nova.visit(path)
        }
      } catch (error) {
        if (error.response?.status === 500) {
          Nova.error(this.__('There was a problem submitting the form.'))
        }
      }
    },
  }
}
</script>

<style>

</style>
