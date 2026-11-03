-- ==============================================================================================
-- ENTERPRISE DATA WAREHOUSE: 15 GUARANTEED BUSINESS QUERIES (ROUNDED + FULL ETL INTEGRATION)
-- ==============================================================================================
-- These queries utilize your entire Star Schema, including the external CSV, JSON, and MySQL 
-- data ingested via your Laravel portal. All calculations are rounded to 2 decimal places.
-- ==============================================================================================

-- Query 1: Average Temperature by Province
-- Business Question: What is the average temperature recorded across each province?
-- Business Importance: Helps regional managers assess climatic comfort levels, impacting energy demand forecasting (like grid cooling load) across the country.
SELECT 
    g.province, 
    ROUND(AVG(f.temperature_c), 2) AS average_temperature
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.province 
ORDER BY average_temperature DESC;

-- Query 2: Max Wind Speed by District
-- Business Question: What is the maximum wind speed recorded in each district?
-- Business Importance: Critical for issuing wind warnings, securing infrastructure, and planning activities sensitive to high winds like construction and shipping.
SELECT 
    g.district, 
    ROUND(MAX(f.wind_speed_kmh), 2) AS max_wind_speed
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.district 
ORDER BY max_wind_speed DESC 
LIMIT 10;

-- Query 3: Total Precipitation by Climate Zone
-- Business Question: How much total precipitation has fallen in each distinct climate zone?
-- Business Importance: Helps understand broad weather patterns crucial for agriculture planning and national water resource management.
SELECT 
    g.climate_zone, 
    ROUND(SUM(f.precipitation_mm), 2) AS total_rainfall_mm
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.climate_zone;

-- Query 4: Hardware Operational Status by Sensor Type (MySQL Data)
-- Business Question: How many sensors of each type are currently online versus offline?
-- Business Importance: Helps in monitoring the operational status of the sensor network, prioritizing hardware maintenance, and ensuring data collection reliability.
SELECT 
    dsh.sensor_type, 
    dsh.status, 
    COUNT(dsh.hardware_id) AS count_of_sensors
FROM `weather-warehouse.weather_data.dim_sensor_hardware` dsh 
GROUP BY dsh.sensor_type, dsh.status 
ORDER BY count_of_sensors DESC;

-- Query 5: Average PM2.5 by River Basin
-- Business Question: What is the average fine particulate matter (PM2.5) pollution level grouped by river basin?
-- Business Importance: Provides insights into how pollution settles in different topographical basins, supporting targeted environmental interventions.
SELECT 
    h.river_basin, 
    ROUND(AVG(f.pm2_5), 2) AS average_pm25
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_topography_hazard` h ON f.location_id = h.location_id 
GROUP BY h.river_basin 
ORDER BY average_pm25 DESC;

-- Query 6: Weather Condition Breakdown
-- Business Question: What are the most frequently occurring weather conditions overall?
-- Business Importance: Establishes a baseline for normal weather operations and helps validate meteorological forecasting accuracy against actual recorded conditions.
SELECT 
    c.condition_name, 
    COUNT(f.fact_id) AS occurrence_count
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_condition` c ON f.weather_code = c.weather_code 
GROUP BY c.condition_name 
ORDER BY occurrence_count DESC;

-- Query 7: Emergency Shelters with Backup Power by District (CSV Data)
-- Business Question: What is the total capacity of emergency shelters equipped with backup power in each district?
-- Business Importance: Helps emergency services assess available resources for disaster response and route displaced civilians to facilities that have reliable electricity.
SELECT 
    dg.district, 
    COUNT(des.shelter_name) AS total_shelters, 
    SUM(des.capacity) AS total_capacity
FROM `weather-warehouse.weather_data.dim_emergency_shelters` des
JOIN `weather-warehouse.weather_data.dim_geography` dg ON des.location_id = dg.location_id
WHERE des.has_backup_power = TRUE
GROUP BY dg.district
ORDER BY total_capacity DESC;

-- Query 8: Average Humidity by Landslide Risk Level
-- Business Question: What is the average relative humidity in areas categorized by different landslide risk levels?
-- Business Importance: Crucial for proactive disaster preparedness. Tracking moisture in high-risk zones helps calibrate early warning evacuation systems.
SELECT 
    h.landslide_risk_level, 
    ROUND(AVG(f.relative_humidity_pct), 2) AS average_humidity
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_topography_hazard` h ON f.location_id = h.location_id 
GROUP BY h.landslide_risk_level;

-- Query 9: Apparent vs Actual Temp Difference by City
-- Business Question: Which cities have the highest average difference between actual temperature and "feels like" temperature?
-- Business Importance: Highlights locations with severe heat stress indices (often driven by high humidity), allowing the Ministry of Health to allocate resources.
SELECT 
    g.city, 
    ROUND(AVG(f.apparent_temp_c - f.temperature_c), 2) AS average_heat_index_difference
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.city 
ORDER BY average_heat_index_difference DESC 
LIMIT 10;

-- Query 10: Demographics & Air Quality Risk (JSON Data)
-- Business Question: What is the average PM10 pollution level in districts with a high percentage of vulnerable elderly populations?
-- Business Importance: Directly links environmental hazards to demographic vulnerability, allowing the Ministry of Health to allocate respiratory care resources effectively.
SELECT 
    dg.district, 
    dd.vulnerable_elderly_pct, 
    ROUND(AVG(f.pm10), 2) AS average_pm10
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f
JOIN `weather-warehouse.weather_data.dim_geography` dg ON f.location_id = dg.location_id
JOIN `weather-warehouse.weather_data.dim_district_demographics` dd ON f.location_id = dd.location_id
GROUP BY dg.district, dd.vulnerable_elderly_pct
ORDER BY average_pm10 DESC;

-- Query 11: Temperature Volatility (Range) by Province
-- Business Question: Which provinces experience the widest swing between their maximum and minimum recorded temperatures?
-- Business Importance: Helps in agricultural planning (selecting crop resilience) and understanding infrastructure stress caused by thermal expansion and contraction.
SELECT 
    g.province, 
    ROUND((MAX(f.temperature_c) - MIN(f.temperature_c)), 2) AS temperature_volatility_range
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.province 
ORDER BY temperature_volatility_range DESC;

-- Query 12: Average Wind Direction by Province
-- Business Question: What is the prevailing average wind direction recorded in each province?
-- Business Importance: Vital for aviation routing, maritime navigation, and optimal placement of wind energy turbines.
SELECT 
    g.province, 
    ROUND(AVG(f.wind_direction_deg), 2) AS average_wind_direction
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_geography` g ON f.location_id = g.location_id 
GROUP BY g.province;

-- Query 13: Precipitation by Flood Vulnerability
-- Business Question: How much total rainfall has accumulated in areas based on their specific flood vulnerability rating?
-- Business Importance: Directly correlates weather events to disaster risk, allowing emergency response teams to pre-position resources before severe flooding occurs.
SELECT 
    h.flood_vulnerability, 
    ROUND(SUM(f.precipitation_mm), 2) AS total_rainfall_mm
FROM `weather-warehouse.weather_data.fact_hourly_environmental` f 
JOIN `weather-warehouse.weather_data.dim_topography_hazard` h ON f.location_id = h.location_id 
GROUP BY h.flood_vulnerability
ORDER BY total_rainfall_mm DESC;

-- Query 14: Emergency Shelter Availability vs Population Density (JSON & CSV Data)
-- Business Question: Are emergency shelters adequately distributed in high-density districts?
-- Business Importance: A purely dimensional strategic query that reveals resource gaps, highlighting where new shelters must be built to protect dense populations.
SELECT 
    dg.district, 
    dd.population_density_km2, 
    SUM(des.capacity) AS total_district_shelter_capacity
FROM `weather-warehouse.weather_data.dim_geography` dg
JOIN `weather-warehouse.weather_data.dim_district_demographics` dd ON dg.location_id = dd.location_id
LEFT JOIN `weather-warehouse.weather_data.dim_emergency_shelters` des ON dg.location_id = des.location_id
GROUP BY dg.district, dd.population_density_km2
ORDER BY dd.population_density_km2 DESC;

-- Query 15: Network Health: Percentage of Offline Sensors by Province (MySQL Data)
-- Business Question: What percentage of our weather sensors are currently failing or offline in each province?
-- Business Importance: Provides an executive overview of sensor network health by region, forcing accountability for regional maintenance teams.
SELECT 
    dg.province, 
    COUNT(dsh.hardware_id) AS total_sensors,
    ROUND(COUNT(CASE WHEN dsh.status = 'Offline' THEN 1 END) * 100.0 / COUNT(dsh.hardware_id), 2) AS offline_percentage
FROM `weather-warehouse.weather_data.dim_sensor_hardware` dsh
JOIN `weather-warehouse.weather_data.dim_geography` dg ON dsh.location_id = dg.location_id
GROUP BY dg.province
ORDER BY offline_percentage DESC;
