<template>
  <div>
    <Head title="Two factor authentication (2FA)" />

    <form
      @submit.prevent="verify"
      class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 max-w-[25rem] mx-auto"
    >
      <h2 class="text-2xl text-center font-normal mb-6 text-90">
        {{ __('Enter your code') }}
      </h2>

      <DividerLine />

      <div class="mb-6">
        <label class="block mb-2" for="code">
          {{ __('Enter the 6-digit security code from your authenticator app.')}}
        </label>
        <input
          v-model="form.code"
          @input="checkAutoSubmit"
          class="form-control form-input form-input-bordered w-full"
          :class="{ 'form-input-border-error': form.errors.has('code') }"
          minlength="6"
          maxlength="6"
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

      <div v-if="recoveryEnabled"
        class="ml-auto"
      >
        <Link
          :href="$url(recoveryUrl)"
          class="text-gray-500 font-bold no-underline"
          v-text="__('Don\'t have a code?')"
        />
      </div>

    </form>
  </div>
</template>

<script>
import Auth from '../../../vendor/laravel/nova/resources/js/layouts/Auth'

export default {
  name: 'Nova2faAuthenticate',

  layout: Auth,

  props: ['recoveryEnabled'],

  data: () => ({
    form: Nova.form({
      code: ''
    })
  }),

  computed: {
    recoveryUrl() {
      return '/nova-2fa/recover';
    }
  },

  mounted() {
    //
  },

  methods: {
    async verify() {
      try {
        const {redirect} = await this.form.post('/nova-vendor/nova-2fa/verify')

        if (redirect !== undefined && redirect !== null) {
          let path = {url: redirect, remote: true}
          Nova.visit(path)
        }
      } catch (error) {
        if (error.response?.status === 500) {
          Nova.error(this.__('There was a problem submitting the form.'))
        }
      }
    },

    checkAutoSubmit() {
      if (this.form.code.length == 6) {
        this.verify();
      }
    }
  }
}
</script>

<style>

</style>
