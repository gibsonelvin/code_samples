import PropTypes from 'prop-types'
import BasicFieldItem from './BasicFieldItem'

export default function EntityDisplay({ entity }) {
	const entityTypesName = [
		'',
		'User',
		'Lab',
		'Audit'
	]

	return (
		<>
			<BasicFieldItem label='Name' value={entity.data.name} />
			<BasicFieldItem label='Email' value={entity.data.email} />
			<BasicFieldItem label='Phone' value={entity.data.phone} />
			<BasicFieldItem label='Description' value={entity.data.description} />
			<BasicFieldItem label='Country' value={entity.data.country} />
			<BasicFieldItem label='State' value={entity.data.state} />
			<BasicFieldItem label='City' value={entity.data.city} />
			<BasicFieldItem label='Street' value={entity.data.street} />
			<BasicFieldItem label='Zip Code' value={entity.data.zipCode} />
			<BasicFieldItem label='Entity Type' value={entityTypesName[entity.data.entityTypeId * -1]} />
		</>
	)
}

EntityDisplay.propTypes = {
	entity: PropTypes.object.isRequired
}
