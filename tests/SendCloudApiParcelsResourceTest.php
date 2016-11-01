<?php
use PHPUnit\Framework\TestCase;
use \Mockery as m;

/**
 * Test the `/parcel` API resource.
 */
class SendCloudApiParcelsResourceTest extends TestCase
{

    /**
     * Clean up the mocks user by the test case.
     */
    public function tearDown()
    {
        m::close();
    }

    /**
     * Regression test: cover a misbehaviour in the API wrapper regarding
     * the usage of the object ID in the reource endpoint.
     *
     * @link https://github.com/SendCloud/SendCloud-API-PHP-Wrapper/issues/12
     */
    public function testPutShouldUseProperEndpointAndPayload()
    {
        $fields = array(
            'name' => 'Foo Bar',
            'address_1' => 'Lorem Ipsum 999'
        );

        $expected_data = array(
            'parcel' => $fields + array('id' => 1)
        );

        $mock_client = m::mock('client');
        $mock_client->shouldReceive('update')
            ->times(1)
            ->with('parcels', $expected_data, 'parcel');

        $resource = new SendCloudApiParcelsResource($mock_client);
        $resource->update(1, $fields);
    }
}
