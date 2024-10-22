<?php
// phpcs:disable PEAR.Commenting

namespace MaxMind\Exception {
	class WebServiceException extends \Exception
	{
	}
	class HttpException extends \MaxMind\Exception\WebServiceException
	{
		public function __construct($message, $httpStatus, $uri, \Exception $previous = null)
		{
		}
		public function getUri()
		{
		}
		public function getStatusCode()
		{
		}
	}
	class InvalidRequestException extends \MaxMind\Exception\HttpException
	{
		public function __construct($message, $error, $httpStatus, $uri, \Exception $previous = null)
		{
		}
		public function getErrorCode()
		{
		}
	}
	class AuthenticationException extends \MaxMind\Exception\InvalidRequestException
	{
	}
	class InsufficientFundsException extends \MaxMind\Exception\InvalidRequestException
	{
	}
	class InvalidInputException extends \MaxMind\Exception\WebServiceException
	{
	}
	class IpAddressNotFoundException extends \MaxMind\Exception\InvalidRequestException
	{
	}
	class PermissionRequiredException extends \MaxMind\Exception\InvalidRequestException
	{
	}
}

namespace MaxMind\WebService {
	class Client
	{
		const VERSION = '0.2.0';
		public function __construct($accountId, $licenseKey, $options = [])
		{
		}
		public function post($service, $path, $input)
		{
		}
		public function get($service, $path)
		{
		}
	}
}

namespace MaxMind\WebService\Http {
	interface Request
	{
		public function __construct($url, $options);
		public function post($body);
		public function get();
	}
	class CurlRequest implements \MaxMind\WebService\Http\Request
	{
		public function __construct($url, $options)
		{
		}
		public function post($body)
		{
		}
		public function get()
		{
		}
	}
	class RequestFactory
	{
		public function __construct()
		{
		}
		public function request($url, $options)
		{
		}
	}
}

namespace GeoIp2 {
	interface ProviderInterface
	{
		/**
		 * @param string $ipAddress an IPv4 or IPv6 address to lookup
		 *
		 * @return \GeoIp2\Model\Country a Country model for the requested IP address
		 */
		public function country(string $ipAddress) : \GeoIp2\Model\Country;
		/**
		 * @param string $ipAddress an IPv4 or IPv6 address to lookup
		 *
		 * @return \GeoIp2\Model\City a City model for the requested IP address
		 */
		public function city(string $ipAddress) : \GeoIp2\Model\City;
	}
}

namespace GeoIp2\Database {
	/**
	 * Instances of this class provide a reader for the GeoIP2 database format.
	 * IP addresses can be looked up using the database specific methods.
	 *
	 * ## Usage ##
	 *
	 * The basic API for this class is the same for every database. First, you
	 * create a reader object, specifying a file name. You then call the method
	 * corresponding to the specific database, passing it the IP address you want
	 * to look up.
	 *
	 * If the request succeeds, the method call will return a model class for
	 * the method you called. This model in turn contains multiple record classes,
	 * each of which represents part of the data returned by the database. If
	 * the database does not contain the requested information, the attributes
	 * on the record class will have a `null` value.
	 *
	 * If the address is not in the database, an
	 * {@link \GeoIp2\Exception\AddressNotFoundException} exception will be
	 * thrown. If an invalid IP address is passed to one of the methods, a
	 * SPL {@link \InvalidArgumentException} will be thrown. If the database is
	 * corrupt or invalid, a {@link \MaxMind\Db\Reader\InvalidDatabaseException}
	 * will be thrown.
	 */
	class Reader implements \GeoIp2\ProviderInterface
	{
		/**
		 * Constructor.
		 *
		 * @param string $filename the path to the GeoIP2 database file
		 * @param array  $locales  list of locale codes to use in name property
		 *                         from most preferred to least preferred
		 *
		 * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
		 *                                                     is corrupt or invalid
		 */
		public function __construct(string $filename, array $locales = ['en'])
		{
		}
		/**
		 * This method returns a GeoIP2 City model.
		 *
		 * @param string $ipAddress an IPv4 or IPv6 address as a string
		 *
		 * @throws \GeoIp2\Exception\AddressNotFoundException  if the address is
		 *                                                     not in the database
		 * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
		 *                                                     is corrupt or invalid
		 */
		public function city(string $ipAddress) : \GeoIp2\Model\City
		{
		}
		/**
		 * This method returns a GeoIP2 Country model.
		 *
		 * @param string $ipAddress an IPv4 or IPv6 address as a string
		 *
		 * @throws \GeoIp2\Exception\AddressNotFoundException  if the address is
		 *                                                     not in the database
		 * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
		 *                                                     is corrupt or invalid
		 */
		public function country(string $ipAddress) : \GeoIp2\Model\Country
		{
		}
		/**
		 * This method returns a GeoIP2 Anonymous IP model.
		 *
		 * @param string $ipAddress an IPv4 or IPv6 address as a string
		 *
		 * @throws \GeoIp2\Exception\AddressNotFoundException  if the address is
		 *                                                     not in the database
		 * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
		 *                                                     is corrupt or invalid
		 */
		public function anonymousIp(string $ipAddress) : \GeoIp2\Model\AnonymousIp
		{
		}
		/**
		 * This method returns a GeoLite2 ASN model.
		 *
		 * @param string $ipAddress an IPv4 or IPv6 address as a string
		 *
		 * @throws \GeoIp2\Exception\AddressNotFoundException  if the address is
		 *                                                     not in the database
		 * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
		 *                                                     is corrupt or invalid
		 */
		public function asn(string $ipAddress) : \GeoIp2\Model\Asn
		{
		}
		/**
		 * This method returns a GeoIP2 Connection Type model.
		 *
		 * @param string $ipAddress an IPv4 or IPv6 address as a string
		 *
		 * @throws \GeoIp2\Exception\AddressNotFoundException  if the address is
		 *                                                     not in the database
		 * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
		 *                                                     is corrupt or invalid
		 */
		public function connectionType(string $ipAddress) : \GeoIp2\Model\ConnectionType
		{
		}
		/**
		 * This method returns a GeoIP2 Domain model.
		 *
		 * @param string $ipAddress an IPv4 or IPv6 address as a string
		 *
		 * @throws \GeoIp2\Exception\AddressNotFoundException  if the address is
		 *                                                     not in the database
		 * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
		 *                                                     is corrupt or invalid
		 */
		public function domain(string $ipAddress) : \GeoIp2\Model\Domain
		{
		}
		/**
		 * This method returns a GeoIP2 Enterprise model.
		 *
		 * @param string $ipAddress an IPv4 or IPv6 address as a string
		 *
		 * @throws \GeoIp2\Exception\AddressNotFoundException  if the address is
		 *                                                     not in the database
		 * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
		 *                                                     is corrupt or invalid
		 */
		public function enterprise(string $ipAddress) : \GeoIp2\Model\Enterprise
		{
		}
		/**
		 * This method returns a GeoIP2 ISP model.
		 *
		 * @param string $ipAddress an IPv4 or IPv6 address as a string
		 *
		 * @throws \GeoIp2\Exception\AddressNotFoundException  if the address is
		 *                                                     not in the database
		 * @throws \MaxMind\Db\Reader\InvalidDatabaseException if the database
		 *                                                     is corrupt or invalid
		 */
		public function isp(string $ipAddress) : \GeoIp2\Model\Isp
		{
		}
		/**
		 * @throws \InvalidArgumentException if arguments are passed to the method
		 * @throws \BadMethodCallException   if the database has been closed
		 *
		 * @return \MaxMind\Db\Reader\Metadata object for the database
		 */
		public function metadata() : \MaxMind\Db\Reader\Metadata
		{
		}
		/**
		 * Closes the GeoIP2 database and returns the resources to the system.
		 */
		public function close() : void
		{
		}
	}
}

namespace GeoIp2\Exception {
	/**
	 * This class represents a generic error.
	 */
	// phpcs:disable
	class GeoIp2Exception extends \Exception
	{
	}
	/**
	 * This class represents a generic error.
	 */
	// phpcs:disable
	class AddressNotFoundException extends \GeoIp2\Exception\GeoIp2Exception
	{
	}
	/**
	 * This class represents a generic error.
	 */
	// phpcs:disable
	class AuthenticationException extends \GeoIp2\Exception\GeoIp2Exception
	{
	}
	/**
	 *  This class represents an HTTP transport error.
	 */
	class HttpException extends \GeoIp2\Exception\GeoIp2Exception
	{
		/**
		 * The URI queried.
		 */
		public string $uri;
		public function __construct(string $message, int $httpStatus, string $uri, \Exception $previous = null)
		{
		}
	}
	/**
	 * This class represents an error returned by MaxMind's GeoIP2
	 * web service.
	 */
	class InvalidRequestException extends \GeoIp2\Exception\HttpException
	{
		/**
		 * The code returned by the MaxMind web service.
		 */
		public string $error;
		public function __construct(string $message, string $error, int $httpStatus, string $uri, \Exception $previous = null)
		{
		}
	}
	/**
	 * This class represents a generic error.
	 */
	// phpcs:disable
	class OutOfQueriesException extends \GeoIp2\Exception\GeoIp2Exception
	{
	}
}

namespace GeoIp2\Model {
	/**
	 * This class provides the GeoIP2 Anonymous IP model.
	 */
	class AnonymousIp implements \JsonSerializable
	{
		/**
		 * @var bool this is true if the IP address belongs to
		 *           any sort of anonymous network
		 */
		public readonly bool $isAnonymous;
		/**
		 * @var bool This is true if the IP address is
		 *           registered to an anonymous VPN provider. If a VPN provider does not
		 *           register subnets under names associated with them, we will likely only
		 *           flag their IP ranges using the isHostingProvider property.
		 */
		public readonly bool $isAnonymousVpn;
		/**
		 * @var bool this is true if the IP address belongs
		 *           to a hosting or VPN provider (see description of isAnonymousVpn property)
		 */
		public readonly bool $isHostingProvider;
		/**
		 * @var bool this is true if the IP address belongs to
		 *           a public proxy
		 */
		public readonly bool $isPublicProxy;
		/**
		 * @var bool this is true if the IP address is
		 *           on a suspected anonymizing network and belongs to a residential ISP
		 */
		public readonly bool $isResidentialProxy;
		/**
		 * @var bool this is true if the IP address is a Tor
		 *           exit node
		 */
		public readonly bool $isTorExitNode;
		/**
		 * @var string the IP address that the data in the model is
		 *             for
		 */
		public readonly string $ipAddress;
		/**
		 * @var string The network in CIDR notation associated with
		 *             the record. In particular, this is the largest network where all of the
		 *             fields besides $ipAddress have the same value.
		 */
		public readonly string $network;
		/**
		 * @ignore
		 */
		public function __construct(array $raw)
		{
		}
		public function jsonSerialize() : ?array
		{
		}
	}
	/**
	 * This class provides the GeoLite2 ASN model.
	 */
	class Asn implements \JsonSerializable
	{
		/**
		 * @var int|null the autonomous system number
		 *               associated with the IP address
		 */
		public readonly ?int $autonomousSystemNumber;
		/**
		 * @var string|null the organization
		 *                  associated with the registered autonomous system number for the IP
		 *                  address
		 */
		public readonly ?string $autonomousSystemOrganization;
		/**
		 * @var string the IP address that the data in the model is
		 *             for
		 */
		public readonly string $ipAddress;
		/**
		 * @var string The network in CIDR notation associated with
		 *             the record. In particular, this is the largest network where all of the
		 *             fields besides $ipAddress have the same value.
		 */
		public readonly string $network;
		/**
		 * @ignore
		 */
		public function __construct(array $raw)
		{
		}
		public function jsonSerialize() : ?array
		{
		}
	}
	/**
	 * Model class for the data returned by GeoIP2 Country web service and database.
	 *
	 * See https://dev.maxmind.com/geoip/docs/web-services?lang=en for more details.
	 */
	class Country implements \JsonSerializable
	{
		/**
		 * @var \GeoIp2\Record\Continent continent data for the
		 *                               requested IP address
		 */
		public readonly \GeoIp2\Record\Continent $continent;
		/**
		 * @var \GeoIp2\Record\Country Country data for the requested
		 *                             IP address. This object represents the country where MaxMind believes the
		 *                             end user is located.
		 */
		public readonly \GeoIp2\Record\Country $country;
		/**
		 * @var \GeoIp2\Record\MaxMind data related to your MaxMind
		 *                             account
		 */
		public readonly \GeoIp2\Record\MaxMind $maxmind;
		/**
		 * @var \GeoIp2\Record\Country Registered country
		 *                             data for the requested IP address. This record represents the country
		 *                             where the ISP has registered a given IP block and may differ from the
		 *                             user's country.
		 */
		public readonly \GeoIp2\Record\Country $registeredCountry;
		/**
		 * @var \GeoIp2\Record\RepresentedCountry * Represented country data for the requested IP address. The represented
		 *                                        country is used for things like military bases. It is only present when
		 *                                        the represented country differs from the country.
		 */
		public readonly \GeoIp2\Record\RepresentedCountry $representedCountry;
		/**
		 * @var \GeoIp2\Record\Traits data for the traits of the
		 *                            requested IP address
		 */
		public readonly \GeoIp2\Record\Traits $traits;
		/**
		 * @ignore
		 */
		public function __construct(array $raw, array $locales = ['en'])
		{
		}
		public function jsonSerialize() : ?array
		{
		}
	}
	/**
	 * Model class for the data returned by City Plus web service and City
	 * database.
	 *
	 * See https://dev.maxmind.com/geoip/docs/web-services?lang=en for more
	 * details.
	 */
	class City extends \GeoIp2\Model\Country
	{
		/**
		 * @var \GeoIp2\Record\City city data for the requested IP
		 *                          address
		 */
		public readonly \GeoIp2\Record\City $city;
		/**
		 * @var \GeoIp2\Record\Location location data for the
		 *                              requested IP address
		 */
		public readonly \GeoIp2\Record\Location $location;
		/**
		 * @var \GeoIp2\Record\Subdivision An object
		 *                                 representing the most specific subdivision returned. If the response
		 *                                 did not contain any subdivisions, this method returns an empty
		 *                                 \GeoIp2\Record\Subdivision object.
		 */
		public readonly \GeoIp2\Record\Subdivision $mostSpecificSubdivision;
		/**
		 * @var \GeoIp2\Record\Postal postal data for the
		 *                            requested IP address
		 */
		public readonly \GeoIp2\Record\Postal $postal;
		/**
		 * @var array<\GeoIp2\Record\Subdivision> An array of \GeoIp2\Record\Subdivision
		 *                                        objects representing the country subdivisions for the requested IP
		 *                                        address. The number and type of subdivisions varies by country, but a
		 *                                        subdivision is typically a state, province, county, etc. Subdivisions
		 *                                        are ordered from most general (largest) to most specific (smallest).
		 *                                        If the response did not contain any subdivisions, this method returns
		 *                                        an empty array.
		 */
		public readonly array $subdivisions;
		/**
		 * @ignore
		 */
		public function __construct(array $raw, array $locales = ['en'])
		{
		}
		public function jsonSerialize() : ?array
		{
		}
	}
	/**
	 * This class provides the GeoIP2 Connection-Type model.
	 */
	class ConnectionType implements \JsonSerializable
	{
		/**
		 * @var string|null The connection type may take the
		 *                  following values: "Dialup", "Cable/DSL", "Corporate", "Cellular", and
		 *                  "Satellite". Additional values may be added in the future.
		 */
		public readonly ?string $connectionType;
		/**
		 * @var string the IP address that the data in the model is
		 *             for
		 */
		public readonly string $ipAddress;
		/**
		 * @var string The network in CIDR notation associated with
		 *             the record. In particular, this is the largest network where all of the
		 *             fields besides $ipAddress have the same value.
		 */
		public readonly string $network;
		/**
		 * @ignore
		 */
		public function __construct(array $raw)
		{
		}
		public function jsonSerialize() : ?array
		{
		}
	}
	/**
	 * This class provides the GeoIP2 Domain model.
	 */
	class Domain implements \JsonSerializable
	{
		/**
		 * @var string|null The second level domain associated with the
		 *                  IP address. This will be something like "example.com" or
		 *                  "example.co.uk", not "foo.example.com".
		 */
		public readonly ?string $domain;
		/**
		 * @var string the IP address that the data in the model is
		 *             for
		 */
		public readonly string $ipAddress;
		/**
		 * @var string The network in CIDR notation associated with
		 *             the record. In particular, this is the largest network where all of the
		 *             fields besides $ipAddress have the same value.
		 */
		public readonly string $network;
		/**
		 * @ignore
		 */
		public function __construct(array $raw)
		{
		}
		public function jsonSerialize() : ?array
		{
		}
	}
	/**
	 * Model class for the data returned by GeoIP2 Enterprise database lookups.
	 *
	 * See https://dev.maxmind.com/geoip/docs/web-services?lang=en for more
	 * details.
	 */
	// phpcs:disable
	class Enterprise extends \GeoIp2\Model\City
	{
	}
	/**
	 * Model class for the data returned by GeoIP2 Insights web service.
	 *
	 * See https://dev.maxmind.com/geoip/docs/web-services?lang=en for
	 * more details.
	 */
	// phpcs:disable
	class Insights extends \GeoIp2\Model\City
	{
	}
	/**
	 * This class provides the GeoIP2 ISP model.
	 */
	class Isp implements \JsonSerializable
	{
		/**
		 * @var int|null the autonomous system number
		 *               associated with the IP address
		 */
		public readonly ?int $autonomousSystemNumber;
		/**
		 * @var string|null the organization
		 *                  associated with the registered autonomous system number for the IP
		 *                  address
		 */
		public readonly ?string $autonomousSystemOrganization;
		/**
		 * @var string|null the name of the ISP associated with the IP
		 *                  address
		 */
		public readonly ?string $isp;
		/**
		 * @var string|null The [mobile country code
		 *                  (MCC)](https://en.wikipedia.org/wiki/Mobile_country_code) associated with
		 *                  the IP address and ISP.
		 */
		public readonly ?string $mobileCountryCode;
		/**
		 * @var string|null The [mobile network code
		 *                  (MNC)](https://en.wikipedia.org/wiki/Mobile_country_code) associated with
		 *                  the IP address and ISP.
		 */
		public readonly ?string $mobileNetworkCode;
		/**
		 * @var string|null the name of the organization associated
		 *                  with the IP address
		 */
		public readonly ?string $organization;
		/**
		 * @var string the IP address that the data in the model is
		 *             for
		 */
		public readonly string $ipAddress;
		/**
		 * @var string The network in CIDR notation associated with
		 *             the record. In particular, this is the largest network where all of the
		 *             fields besides $ipAddress have the same value.
		 */
		public readonly string $network;
		/**
		 * @ignore
		 */
		public function __construct(array $raw)
		{
		}
		public function jsonSerialize() : ?array
		{
		}
	}
}

namespace GeoIp2\Record {
	abstract class AbstractNamedRecord implements \JsonSerializable
	{
		/**
		 * @var string|null The name based on the locales list
		 *                  passed to the constructor. This attribute is returned by all location
		 *                  services and databases.
		 */
		public readonly ?string $name;
		/**
		 * @var array An array map where the keys are locale codes
		 *            and the values are names. This attribute is returned by all location
		 *            services and databases.
		 */
		public readonly array $names;
		/**
		 * @ignore
		 */
		public function __construct(array $record, array $locales = ['en'])
		{
		}
		public function jsonSerialize() : array
		{
		}
	}
	abstract class AbstractPlaceRecord extends \GeoIp2\Record\AbstractNamedRecord
	{
		/**
		 * @var int|null A value from 0-100 indicating MaxMind's
		 *               confidence that the location level is correct. This attribute is only available
		 *               from the Insights service and the GeoIP2 Enterprise database.
		 */
		public readonly ?int $confidence;
		/**
		 * @var int|null The GeoName ID for the location level. This attribute
		 *               is returned by all location services and databases.
		 */
		public readonly ?int $geonameId;
		/**
		 * @ignore
		 */
		public function __construct(array $record, array $locales = ['en'])
		{
		}
		public function jsonSerialize() : array
		{
		}
	}
	/**
	 * City-level data associated with an IP address.
	 *
	 * This record is returned by all location services and databases besides
	 * Country.
	 */
	// phpcs:disable
	class City extends \GeoIp2\Record\AbstractPlaceRecord
	{
	}
	/**
	 * Contains data for the continent record associated with an IP address.
	 *
	 * This record is returned by all location services and databases.
	 */
	class Continent extends \GeoIp2\Record\AbstractNamedRecord
	{
		/**
		 * @var string|null A two character continent code like "NA" (North
		 *                  America) or "OC" (Oceania). This attribute is returned by all location
		 *                  services and databases.
		 */
		public readonly ?string $code;
		/**
		 * @var int|null The GeoName ID for the continent. This
		 *               attribute is returned by all location services and databases.
		 */
		public readonly ?int $geonameId;
		/**
		 * @ignore
		 */
		public function __construct(array $record, array $locales = ['en'])
		{
		}
		public function jsonSerialize() : array
		{
		}
	}
	/**
	 * Contains data for the country record associated with an IP address.
	 *
	 * This record is returned by all location services and databases.
	 */
	class Country extends \GeoIp2\Record\AbstractPlaceRecord
	{
		/**
		 * @var bool This is true if the country is a
		 *           member state of the European Union. This attribute is returned by all
		 *           location services and databases.
		 */
		public readonly bool $isInEuropeanUnion;
		/**
		 * @var string|null The two-character ISO 3166-1 alpha code
		 *                  for the country. See https://en.wikipedia.org/wiki/ISO_3166-1. This
		 *                  attribute is returned by all location services and databases.
		 */
		public readonly ?string $isoCode;
		/**
		 * @ignore
		 */
		public function __construct(array $record, array $locales = ['en'])
		{
		}
		public function jsonSerialize() : array
		{
		}
	}
	/**
	 * Contains data for the location record associated with an IP address.
	 *
	 * This record is returned by all location services and databases besides
	 * Country.
	 */
	class Location implements \JsonSerializable
	{
		/**
		 * @var int|null The average income in US dollars
		 *               associated with the requested IP address. This attribute is only available
		 *               from the Insights service.
		 */
		public readonly ?int $averageIncome;
		/**
		 * @var int|null The approximate accuracy radius in
		 *               kilometers around the latitude and longitude for the IP address. This is
		 *               the radius where we have a 67% confidence that the device using the IP
		 *               address resides within the circle centered at the latitude and longitude
		 *               with the provided radius.
		 */
		public readonly ?int $accuracyRadius;
		/**
		 * @var float|null The approximate latitude of the location
		 *                 associated with the IP address. This value is not precise and should not be
		 *                 used to identify a particular address or household.
		 */
		public readonly ?float $latitude;
		/**
		 * @var float|null The approximate longitude of the location
		 *                 associated with the IP address. This value is not precise and should not be
		 *                 used to identify a particular address or household.
		 */
		public readonly ?float $longitude;
		/**
		 * @var int|null The metro code of the location if the location
		 *               is in the US. MaxMind returns the same metro codes as the
		 *               Google AdWords API. See
		 *               https://developers.google.com/adwords/api/docs/appendix/cities-DMAregions.
		 */
		public readonly ?int $metroCode;
		/**
		 * @var int|null The estimated population per square
		 *               kilometer associated with the IP address. This attribute is only available
		 *               from the Insights service.
		 */
		public readonly ?int $populationDensity;
		/**
		 * @var string|null The time zone associated with location, as
		 *                  specified by the IANA Time Zone Database, e.g., "America/New_York". See
		 *                  https://www.iana.org/time-zones.
		 */
		public readonly ?string $timeZone;
		public function __construct(array $record)
		{
		}
		public function jsonSerialize() : array
		{
		}
	}
	/**
	 * Contains data about your account.
	 *
	 * This record is returned by all location services and databases.
	 */
	class MaxMind implements \JsonSerializable
	{
		/**
		 * @var int|null the number of remaining queries you
		 *               have for the service you are calling
		 */
		public readonly ?int $queriesRemaining;
		public function __construct(array $record)
		{
		}
		public function jsonSerialize() : array
		{
		}
	}
	/**
	 * Contains data for the postal record associated with an IP address.
	 *
	 * This record is returned by all location databases and services besides
	 * Country.
	 */
	class Postal implements \JsonSerializable
	{
		/**
		 * @var string|null The postal code of the location. Postal codes
		 *                  are not available for all countries. In some countries, this will only
		 *                  contain part of the postal code. This attribute is returned by all location
		 *                  databases and services besides Country.
		 */
		public readonly ?string $code;
		/**
		 * @var int|null A value from 0-100 indicating MaxMind's
		 *               confidence that the postal code is correct. This attribute is only
		 *               available from the Insights service and the GeoIP2 Enterprise
		 *               database.
		 */
		public readonly ?int $confidence;
		/**
		 * @ignore
		 */
		public function __construct(array $record)
		{
		}
		public function jsonSerialize() : array
		{
		}
	}
	/**
	 * Contains data for the represented country associated with an IP address.
	 *
	 * This class contains the country-level data associated with an IP address
	 * for the IP's represented country. The represented country is the country
	 * represented by something like a military base.
	 */
	class RepresentedCountry extends \GeoIp2\Record\Country
	{
		/**
		 * @var string|null A string indicating the type of entity that is
		 *                  representing the country. Currently we only return <code>military</code>
		 *                  but this could expand to include other types in the future.
		 */
		public readonly ?string $type;
		/**
		 * @ignore
		 */
		public function __construct(array $record, array $locales = ['en'])
		{
		}
		public function jsonSerialize() : array
		{
		}
	}
	/**
	 * Contains data for the subdivisions associated with an IP address.
	 *
	 * This record is returned by all location databases and services besides
	 * Country.
	 */
	class Subdivision extends \GeoIp2\Record\AbstractPlaceRecord
	{
		/**
		 * @var string|null This is a string up to three characters long
		 *                  contain the subdivision portion of the ISO 3166-2 code. See
		 *                  https://en.wikipedia.org/wiki/ISO_3166-2. This attribute is returned by all
		 *                  location databases and services except Country.
		 */
		public readonly ?string $isoCode;
		/**
		 * @ignore
		 */
		public function __construct(array $record, array $locales = ['en'])
		{
		}
		public function jsonSerialize() : array
		{
		}
	}
	/**
	 * Contains data for the traits record associated with an IP address.
	 *
	 * This record is returned by all location services and databases.
	 */
	class Traits implements \JsonSerializable
	{
		/**
		 * @var int|null The autonomous system number
		 *               associated with the IP address. See
		 *               https://en.wikipedia.org/wiki/Autonomous_system_(Internet%29. This attribute
		 *               is only available from the City Plus and Insights web services and the
		 *               GeoIP2 Enterprise database.
		 */
		public readonly ?int $autonomousSystemNumber;
		/**
		 * @var string|null The organization
		 *                  associated with the registered autonomous system number for the IP address.
		 *                  See https://en.wikipedia.org/wiki/Autonomous_system_(Internet%29. This
		 *                  attribute is only available from the City Plus and Insights web services and
		 *                  the GeoIP2 Enterprise database.
		 */
		public readonly ?string $autonomousSystemOrganization;
		/**
		 * @var string|null The connection type may take the
		 *                  following  values: "Dialup", "Cable/DSL", "Corporate", "Cellular", and
		 *                  "Satellite". Additional values may be added in the future. This attribute is
		 *                  only available from the City Plus and Insights web services and the GeoIP2
		 *                  Enterprise database.
		 */
		public readonly ?string $connectionType;
		/**
		 * @var string|null The second level domain associated with the
		 *                  IP address. This will be something like "example.com" or "example.co.uk",
		 *                  not "foo.example.com". This attribute is only available from the
		 *                  City Plus and Insights web services and the GeoIP2 Enterprise
		 *                  database.
		 */
		public readonly ?string $domain;
		/**
		 * @var string|null The IP address that the data in the model
		 *                  is for. If you performed a "me" lookup against the web service, this
		 *                  will be the externally routable IP address for the system the code is
		 *                  running on. If the system is behind a NAT, this may differ from the IP
		 *                  address locally assigned to it. This attribute is returned by all end
		 *                  points.
		 */
		public readonly ?string $ipAddress;
		/**
		 * @var bool This is true if the IP address belongs to
		 *           any sort of anonymous network. This property is only available from GeoIP2
		 *           Insights.
		 */
		public readonly bool $isAnonymous;
		/**
		 * @var bool This is true if the IP address is
		 *           registered to an anonymous VPN provider. If a VPN provider does not register
		 *           subnets under names associated with them, we will likely only flag their IP
		 *           ranges using the isHostingProvider property. This property is only available
		 *           from GeoIP2 Insights.
		 */
		public readonly bool $isAnonymousVpn;
		/**
		 * @var bool This is true if the IP address belongs to an [anycast
		 *           network](https://en.wikipedia.org/wiki/Anycast). This property is not
		 *           available from GeoLite databases or web services.
		 */
		public readonly bool $isAnycast;
		/**
		 * @var bool This is true if the IP address belongs
		 *           to a hosting or VPN provider (see description of isAnonymousVpn property).
		 *           This property is only available from GeoIP2 Insights.
		 */
		public readonly bool $isHostingProvider;
		/**
		 * @var bool This attribute is true if MaxMind
		 *           believes this IP address to be a legitimate proxy, such as an internal
		 *           VPN used by a corporation. This attribute is only available in the GeoIP2
		 *           Enterprise database.
		 */
		public readonly bool $isLegitimateProxy;
		/**
		 * @var bool This is true if the IP address belongs to
		 *           a public proxy. This property is only available from GeoIP2 Insights.
		 */
		public readonly bool $isPublicProxy;
		/**
		 * @var bool This is true if the IP address is
		 *           on a suspected anonymizing network and belongs to a residential ISP. This
		 *           property is only available from GeoIP2 Insights.
		 */
		public readonly bool $isResidentialProxy;
		/**
		 * @var bool This is true if the IP address is a Tor
		 *           exit node. This property is only available from GeoIP2 Insights.
		 */
		public readonly bool $isTorExitNode;
		/**
		 * @var string|null The name of the ISP associated with the IP
		 *                  address. This attribute is only available from the City Plus and Insights
		 *                  web services and the GeoIP2 Enterprise database.
		 */
		public readonly ?string $isp;
		/**
		 * @var string|null The [mobile country code
		 *                  (MCC)](https://en.wikipedia.org/wiki/Mobile_country_code) associated with
		 *                  the IP address and ISP. This property is available from the City Plus and
		 *                  Insights web services and the GeoIP2 Enterprise database.
		 */
		public readonly ?string $mobileCountryCode;
		/**
		 * @var string|null The [mobile network code
		 *                  (MNC)](https://en.wikipedia.org/wiki/Mobile_country_code) associated with
		 *                  the IP address and ISP. This property is available from the City Plus and
		 *                  Insights web services and the GeoIP2 Enterprise database.
		 */
		public readonly ?string $mobileNetworkCode;
		/**
		 * @var string|null The network in CIDR notation associated with
		 *                  the record. In particular, this is the largest network where all of the
		 *                  fields besides $ipAddress have the same value.
		 */
		public readonly ?string $network;
		/**
		 * @var string|null The name of the organization
		 *                  associated with the IP address. This attribute is only available from the
		 *                  City Plus and Insights web services and the GeoIP2 Enterprise database.
		 */
		public readonly ?string $organization;
		/**
		 * @var float|null An indicator of how static or
		 *                 dynamic an IP address is. This property is only available from GeoIP2
		 *                 Insights.
		 */
		public readonly ?float $staticIpScore;
		/**
		 * @var int|null The estimated number of users sharing
		 *               the IP/network during the past 24 hours. For IPv4, the count is for the
		 *               individual IP. For IPv6, the count is for the /64 network. This property is
		 *               only available from GeoIP2 Insights.
		 */
		public readonly ?int $userCount;
		/**
		 * @var string|null <p>The user type associated with the IP
		 *  address. This can be one of the following values:</p>
		 *  <ul>
		 *    <li>business
		 *    <li>cafe
		 *    <li>cellular
		 *    <li>college
		 *    <li>consumer_privacy_network
		 *    <li>content_delivery_network
		 *    <li>dialup
		 *    <li>government
		 *    <li>hosting
		 *    <li>library
		 *    <li>military
		 *    <li>residential
		 *    <li>router
		 *    <li>school
		 *    <li>search_engine_spider
		 *    <li>traveler
		 * </ul>
		 * <p>
		 *   This attribute is only available from the Insights web service and the
		 *   GeoIP2 Enterprise database.
		 * </p>
		 */
		public readonly ?string $userType;
		public function __construct(array $record)
		{
		}
		public function jsonSerialize() : array
		{
		}
	}
}

namespace GeoIp2 {
	class Util
	{
		/**
		 * This returns the network in CIDR notation for the given IP and prefix
		 * length. This is for internal use only.
		 *
		 * @internal
		 *
		 * @ignore
		 */
		public static function cidr(string $ipAddress, int $prefixLen) : string
		{
		}
	}
}

namespace GeoIp2\WebService {
	/**
	 * This class provides a client API for all the GeoIP2 web services.
	 * The services are Country, City Plus, and Insights. Each service returns
	 * a different set of data about an IP address, with Country returning the
	 * least data and Insights the most.
	 *
	 * Each web service is represented by a different model class, and these model
	 * classes in turn contain multiple record classes. The record classes have
	 * attributes which contain data about the IP address.
	 *
	 * If the web service does not return a particular piece of data for an IP
	 * address, the associated attribute is not populated.
	 *
	 * The web service may not return any information for an entire record, in
	 * which case all of the attributes for that record class will be empty.
	 *
	 * ## Usage ##
	 *
	 * The basic API for this class is the same for all of the web service end
	 * points. First you create a web service object with your MaxMind `$accountId`
	 * and `$licenseKey`, then you call the method corresponding to a specific end
	 * point, passing it the IP address you want to look up.
	 *
	 * If the request succeeds, the method call will return a model class for
	 * the service you called. This model in turn contains multiple record
	 * classes, each of which represents part of the data returned by the web
	 * service.
	 *
	 * If the request fails, the client class throws an exception.
	 */
	class Client implements \GeoIp2\ProviderInterface
	{
		public const VERSION = 'v3.0.0';
		/**
		 * Constructor.
		 *
		 * @param int    $accountId  your MaxMind account ID
		 * @param string $licenseKey your MaxMind license key
		 * @param array  $locales    list of locale codes to use in name property
		 *                           from most preferred to least preferred
		 * @param array  $options    array of options. Valid options include:
		 *                           * `host` - The host to use when querying the web
		 *                           service. To query the GeoLite2 web service
		 *                           instead of the GeoIP2 web service, set the
		 *                           host to `geolite.info`. To query the Sandbox
		 *                           GeoIP2 web service instead of the production
		 *                           GeoIP2 web service, set the host to
		 *                           `sandbox.maxmind.com`. The sandbox allows you to
		 *                           experiment with the API without affecting your
		 *                           production data.
		 *                           * `timeout` - Timeout in seconds.
		 *                           * `connectTimeout` - Initial connection timeout in seconds.
		 *                           * `proxy` - The HTTP proxy to use. May include a schema, port,
		 *                           username, and password, e.g.,
		 *                           `http://username:password@127.0.0.1:10`.
		 */
		public function __construct(int $accountId, string $licenseKey, array $locales = ['en'], array $options = [])
		{
		}
		/**
		 * This method calls the City Plus service.
		 *
		 * @param string $ipAddress IPv4 or IPv6 address as a string. If no
		 *                          address is provided, the address that the web service is called
		 *                          from will be used.
		 *
		 * @throws \GeoIp2\Exception\AddressNotFoundException if the address you
		 *                                                    provided is not in our database (e.g., a private address).
		 * @throws \GeoIp2\Exception\AuthenticationException  if there is a problem
		 *                                                    with the account ID or license key that you provided
		 * @throws \GeoIp2\Exception\OutOfQueriesException    if your account is out
		 *                                                    of queries
		 * @throws \GeoIp2\Exception\InvalidRequestException} if your request was received by the web service but is
		 *                                                    invalid for some other reason.  This may indicate an issue
		 *                                                    with this API. Please report the error to MaxMind.
		 * @throws \GeoIp2\Exception\HttpException   if an unexpected HTTP error code or message was returned.
		 *                                           This could indicate a problem with the connection between
		 *                                           your server and the web service or that the web service
		 *                                           returned an invalid document or 500 error code
		 * @throws \GeoIp2\Exception\GeoIp2Exception This serves as the parent
		 *                                           class to the above exceptions. It will be thrown directly
		 *                                           if a 200 status code is returned but the body is invalid.
		 * @throws \InvalidArgumentException         if something other than a single IP address or "me" is
		 *                                           passed to the method
		 */
		public function city(string $ipAddress = 'me') : \GeoIp2\Model\City
		{
		}
		/**
		 * This method calls the Country service.
		 *
		 * @param string $ipAddress IPv4 or IPv6 address as a string. If no
		 *                          address is provided, the address that the web service is called
		 *                          from will be used.
		 *
		 * @throws \GeoIp2\Exception\AddressNotFoundException if the address you provided is not in our database (e.g.,
		 *                                                    a private address).
		 * @throws \GeoIp2\Exception\AuthenticationException  if there is a problem
		 *                                                    with the account ID or license key that you provided
		 * @throws \GeoIp2\Exception\OutOfQueriesException    if your account is out of queries
		 * @throws \GeoIp2\Exception\InvalidRequestException} if your request was received by the web service but is
		 *                                                    invalid for some other reason.  This may indicate an
		 *                                                    issue with this API. Please report the error to MaxMind.
		 * @throws \GeoIp2\Exception\HttpException   if an unexpected HTTP error
		 *                                           code or message was returned. This could indicate a problem
		 *                                           with the connection between your server and the web service
		 *                                           or that the web service returned an invalid document or 500
		 *                                           error code.
		 * @throws \GeoIp2\Exception\GeoIp2Exception This serves as the parent class to the above exceptions. It
		 *                                           will be thrown directly if a 200 status code is returned but
		 *                                           the body is invalid.
		 * @throws \InvalidArgumentException         if something other than a single IP address or "me" is
		 *                                           passed to the method
		 */
		public function country(string $ipAddress = 'me') : \GeoIp2\Model\Country
		{
		}
		/**
		 * This method calls the Insights service. Insights is only supported by
		 * the GeoIP2 web service. The GeoLite2 web service does not support it.
		 *
		 * @param string $ipAddress IPv4 or IPv6 address as a string. If no
		 *                          address is provided, the address that the web service is called
		 *                          from will be used.
		 *
		 * @throws \GeoIp2\Exception\AddressNotFoundException if the address you
		 *                                                    provided is not in our database (e.g., a private address).
		 * @throws \GeoIp2\Exception\AuthenticationException  if there is a problem
		 *                                                    with the account ID or license key that you provided
		 * @throws \GeoIp2\Exception\OutOfQueriesException    if your account is out
		 *                                                    of queries
		 * @throws \GeoIp2\Exception\InvalidRequestException} if your request was received by the web service but is
		 *                                                    invalid for some other reason.  This may indicate an
		 *                                                    issue with this API. Please report the error to MaxMind.
		 * @throws \GeoIp2\Exception\HttpException   if an unexpected HTTP error code or message was returned.
		 *                                           This could indicate a problem with the connection between
		 *                                           your server and the web service or that the web service
		 *                                           returned an invalid document or 500 error code
		 * @throws \GeoIp2\Exception\GeoIp2Exception This serves as the parent
		 *                                           class to the above exceptions. It will be thrown directly
		 *                                           if a 200 status code is returned but the body is invalid.
		 * @throws \InvalidArgumentException         if something other than a single IP address or "me" is
		 *                                           passed to the method
		 */
		public function insights(string $ipAddress = 'me') : \GeoIp2\Model\Insights
		{
		}
	}
}
