<?php
/**
 * Plugin Name: Gravity Forms CPF/CNPJ
 * Plugin URI: https://github.com/tifaucz/gravityforms-cpfcnpj/
 * Description: Valida CPF e CNPJ no Gravity Forms, utilize as classes valida_cpf ou valida_cnpj respectivamente em campos de texto. Para as máscaras do campo utilize 999.999.999-99 ou 99.999.999/9999-99
 * Version: 0.5
 * Author: Tiago Faucz
 * Author URI: https://www.norgb.io/
 */

add_filter( 'gform_validation', 'custom_validation' );
function custom_validation( $validation_result ) 
{
    $form = $validation_result['form'];

    foreach ($form['fields'] as &$field) 
    { 
        $id_cpf = false;
        $id_cnpj = false;
        $field_classes = explode(' ', $field->cssClass);
        foreach ($field_classes as $v) 
        {
            if(strtolower($v) == 'valida_cpf')
            {
                $id_cpf = $field->id;
                break;
            }
            if(strtolower($v) == 'valida_cnpj')
            {
                $id_cnpj = $field->id;
                break;
            }
        }
        if($id_cpf && rgpost( 'input_'.$id_cpf ) && !validaCPF(rgpost( 'input_'.$id_cpf )))
        {
            $validation_result['is_valid'] = false;
            $form['is_valid'] = false;
            $field->failed_validation = true;
            $field->validation_message = 'Número de CPF inválido!';
        }
        if($id_cnpj && rgpost( 'input_'.$id_cnpj ) && !validaCNPJ(rgpost( 'input_'.$id_cnpj )))
        {
            $validation_result['is_valid'] = false;
            $form['is_valid'] = false;
            $field->failed_validation = true;
            $field->validation_message = 'Número de CNPJ inválido!';
        }
    }
    $validation_result['form'] = $form;
    return $validation_result;
}

function validaCPF($cpf) 
{
    // Extrai somente os números
    $cpf = preg_replace( '/[^0-9]/is', '', $cpf );
    // Verifica se foi informado todos os digitos corretamente
    if (strlen($cpf) != 11) 
    {
        return false;
    }
    // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
    if (preg_match('/(\d)\1{10}/', $cpf)) 
    {
        return false;
    }
    // Faz o calculo para validar o CPF
    for ($t = 9; $t < 11; $t++) 
    {
        for ($d = 0, $c = 0; $c < $t; $c++) 
        {
            $d += $cpf{$c} * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf{$c} != $d) 
        {
            return false;
        }
    }
    return true;
}

function validaCNPJ($cnpj)
{
    $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
    
    // Valida tamanho
    if (strlen($cnpj) != 14)
        return false;

    // Verifica se todos os digitos são iguais
    if (preg_match('/(\d)\1{13}/', $cnpj))
        return false;   

    // Valida primeiro dígito verificador
    for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
    {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }

    $resto = $soma % 11;

    if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
        return false;

    // Valida segundo dígito verificador
    for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
    {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }

    $resto = $soma % 11;

    return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
}