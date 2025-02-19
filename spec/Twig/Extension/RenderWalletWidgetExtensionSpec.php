<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace spec\BitBag\SyliusAmazonPayPlugin\Twig\Extension;

use BitBag\SyliusAmazonPayPlugin\Resolver\PaymentMethodResolverInterface;
use BitBag\SyliusAmazonPayPlugin\Twig\Extension\RenderWalletWidgetExtension;
use Payum\Core\Model\GatewayConfigInterface;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class RenderWalletWidgetExtensionSpec extends ObjectBehavior
{
    function let(
        Environment $templating,
        PaymentMethodResolverInterface $paymentMethodResolver,
        CartContextInterface $cartContext
    ): void {
        $this->beConstructedWith($templating, $paymentMethodResolver, $cartContext);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(RenderWalletWidgetExtension::class);
    }

    function it_extends_twig_extension(): void
    {
        $this->shouldHaveType(\Twig_Extension::class);
    }

    function it_returns_functions(): void
    {
        $functions = $this->getFunctions();
        $functions->shouldHaveCount(1);
        foreach ($functions as $function) {
            $function->shouldHaveType(\Twig_SimpleFunction::class);
        }
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    function it_returns_empty_string_on_null_payment_method_current(
        CartContextInterface $cartContext,
        OrderInterface $order,
        PaymentInterface $payment
    ): void {
        $cartContext->getCart()->willReturn($order);

        $payment->getMethod()->willReturn(null);
        $order->getLastPayment()->willReturn($payment);

        $this->renderWalletWidget()->shouldReturn('');
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    function it_returns_empty_string_on_null_payment_method(
        CartContextInterface $cartContext,
        OrderInterface $order,
        PaymentMethodInterface $paymentMethod,
        PaymentInterface $payment,
        PaymentMethodResolverInterface $paymentMethodResolver,
        GatewayConfigInterface $gatewayConfig
    ): void {
        $cartContext->getCart()->willReturn($order);
        $payment->getMethod()->willReturn($paymentMethod);
        $order->getLastPayment()->willReturn($payment);

        $gatewayConfig->getConfig()->willReturn('amazonpay');
        $paymentMethod->getGatewayConfig()->willReturn($gatewayConfig);

        $gatewayConfig->getConfig()->willReturn([]);
        $paymentMethod->getGatewayConfig()->willReturn($gatewayConfig);
        $paymentMethodResolver->resolvePaymentMethod('amazonpay')->willReturn(null);

        $this->renderWalletWidget()->shouldReturn('');
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    function it_renders_wallet_widget(
        PaymentMethodResolverInterface $paymentMethodResolver,
        PaymentMethodInterface $paymentMethod,
        PaymentInterface $payment,
        CartContextInterface $cartContext,
        OrderInterface $order,
        Environment $templating,
        GatewayConfigInterface $gatewayConfig
    ): void {
        $cartContext->getCart()->willReturn($order);

        $payment->getMethod()->willReturn($paymentMethod);
        $order->getLastPayment()->willReturn($payment);

        $gatewayConfig->getConfig()->willReturn(['type' => 'amazonpay']);
        $paymentMethod->getGatewayConfig()->willReturn($gatewayConfig);
        $paymentMethodResolver->resolvePaymentMethod('amazonpay')->willReturn($paymentMethod);

        $payment->getDetails()->willReturn(['amazon_pay' => [
            'amazon_order_reference_id' => 123,
        ]]);
        $order->getLastPayment()->willReturn($payment);

        $templating->render('@BitBagSyliusAmazonPayPlugin/AmazonPay/Wallet/_widget.html.twig', [
            'config' => ['type' => 'amazonpay'], 'amazonOrderReferenceId' => 123, ])->willReturn('content');

        $this->renderWalletWidget()->shouldReturn('content');
    }
}
